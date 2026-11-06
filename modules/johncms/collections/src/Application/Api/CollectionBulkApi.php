<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionBulkPreviewDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Services\CollectionRelationDeletion;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionItemUseCase;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionItemWriteAccessUseCase;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;

final readonly class CollectionBulkApi implements CollectionBulkApiInterface
{
    public const int LIMIT = 100;

    public function __construct(
        private CollectionTransactionInterface $transaction,
        private ContentCollectionRepositoryInterface $collections,
        private ContentCollectionItemRepositoryInterface $items,
        private ContentCollectionSectionRepositoryInterface $sections,
        private ContentCollectionFieldRepositoryInterface $fields,
        private EnsureCollectionContentAccessUseCase $access,
        private EnsureCollectionItemWriteAccessUseCase $writes,
        private CollectionsUserApiInterface $userApi,
        private DeleteCollectionItemUseCase $delete,
        private CollectionRelationDeletion $relations,
    ) {
    }

    /** @return array<string, string> */
    public static function actions(): array
    {
        return ['publish' => d__('collections', 'Publish now'), 'hide' => d__('collections', 'Hide items'), 'move' => d__('collections', 'Move items'), 'delete' => d__('collections', 'Delete items')];
    }

    public function preview(int $collectionId, array $ids, string $action, ?int $targetSectionId = null): CollectionBulkPreviewDTO
    {
        return $this->transaction->run(fn (): CollectionBulkPreviewDTO => $this->context($collectionId, $ids, $action, $targetSectionId));
    }

    public function execute(int $collectionId, array $ids, string $action, string $revision, ?int $targetSectionId = null): int
    {
        return $this->transaction->run(function () use ($collectionId, $ids, $action, $revision, $targetSectionId): int {
            $preview = $this->context($collectionId, $ids, $action, $targetSectionId);
            if (! hash_equals($preview->revision, $revision)) {
                throw new CollectionDeletionException(['revision' => [d__('collections', 'The selection changed after confirmation. Nothing was changed. Open a new confirmation.')]]);
            }
            if (! $preview->canExecute()) {
                throw new CollectionDeletionException(['relations' => [d__('collections', 'Relations or field defaults prevent deletion. Remove the references or change their deletion policy first.')]]);
            }
            foreach ($preview->ids as $id) {
                if ($action === 'delete') {
                    $this->access->executeAction($collectionId, 'delete');
                    $this->delete->execute($id, $collectionId, $preview->ids);
                } else {
                    $this->userApi->patchItem($collectionId, $id, new CollectionItemPatchDTO($this->attributes($action, $targetSectionId)));
                }
            }
            return count($preview->ids);
        });
    }

    /** @return array<string, mixed> */
    private function attributes(string $action, ?int $target): array
    {
        return match ($action) {
            'publish' => ['active' => true, 'activeFrom' => null, 'activeTo' => null],
            'hide' => ['active' => false],
            'move' => ['sectionId' => $target],
            default => [],
        };
    }

    /** Current locking reads make the same policy apply to preview and execution.
     * @param list<int> $ids
     */
    private function context(int $collectionId, array $ids, string $action, ?int $target): CollectionBulkPreviewDTO
    {
        $this->access->execute($collectionId);
        if (! array_is_list($ids) || $ids === [] || count($ids) > self::LIMIT || ! in_array($action, ['publish', 'hide', 'move', 'delete'], true)) {
            throw $this->invalid();
        }
        foreach ($ids as $id) {
            if (! is_int($id) || $id < 1) {
                throw $this->invalid();
            }
        }
        if (count(array_unique($ids)) !== count($ids) || ($action !== 'move' && $target !== null)) {
            throw $this->invalid();
        }
        sort($ids);
        $collection = $this->collections->findForUpdate($collectionId) ?? throw $this->invalid();
        $this->access->executeAction($collectionId, $action === 'delete' ? 'delete' : 'edit');
        if (in_array($action, ['publish', 'hide'], true)) {
            $this->access->executeAction($collectionId, 'publish');
        }
        $sections = $this->sections->getAllForUpdate($collectionId);
        $targetSection = $target !== null ? $sections->firstWhere('id', $target) : null;
        if ($action === 'move' && ((empty($collection->settings['has_sections']) && $target !== null) || ($target !== null && $targetSection === null))) {
            throw new CollectionItemFormException(['section_id' => [d__('collections', 'Choose a section of this collection.')]]);
        }
        $definitions = $this->fields->getForUpdate($collectionId);
        $evidence = [$collection->getRawOriginal(), $action, $ids, $target, $sections->map(static fn ($section): array => $section->getRawOriginal())->all(), $definitions->map(static fn ($field): array => $field->getRawOriginal())->all()];
        $names = [];
        foreach ($ids as $id) {
            $item = $this->items->findWithValuesForUpdate($id);
            if ($item === null || $item->collection_id !== $collectionId) {
                throw new CollectionItemFormException(['ids' => [d__('collections', 'Every selected item must still belong to this collection. Nothing was changed.')]]);
            }
            if ($action !== 'delete') {
                $this->writes->execute($collectionId, $id, $this->attributes($action, $target));
            }
            $names[] = $item->name;
            $attributes = $item->getRawOriginal();
            unset($attributes['view_count']);
            $values = [];
            foreach ($item->values as $value) {
                $values[] = $value->getRawOriginal();
            }
            $evidence[] = [$attributes, $values];
        }
        $impact = $action === 'delete' ? $this->relations->impact($collectionId, $ids, $ids, true) : null;
        $evidence[] = $impact?->revision;
        return new CollectionBulkPreviewDTO($action, $ids, $names, hash('sha256', serialize($evidence)), $target, $targetSection?->name, $impact);
    }

    private function invalid(): CollectionItemFormException
    {
        return new CollectionItemFormException(['ids' => [d__('collections', 'Choose an available bulk action and between 1 and 100 distinct records.')]]);
    }
}
