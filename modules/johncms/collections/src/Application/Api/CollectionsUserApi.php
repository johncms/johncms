<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPageDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionTreeNodeDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionDeletionPreviewDTO;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Application\Exceptions\CollectionAccessDeniedException;

final readonly class CollectionsUserApi implements CollectionsUserApiInterface
{
    public function __construct(
        private EnsureCollectionContentAccessUseCase $guard,
        private CollectionsApiInterface $items,
        private CollectionSectionsApiInterface $sections,
        private CurrentUser $user,
        private \Johncms\Modules\Collections\Application\Services\CollectionTagSuggestions $tags,
        private \Johncms\Modules\Collections\Application\UseCases\EnsureCollectionRelationAccessUseCase $relations,
        private \Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface $collections,
        private \Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface $transaction,
    ) {
    }

    public function suggestTags(int $collectionId, string $fieldCode, string $prefix = '', int $limit = 10): array
    {
        $this->guard->execute($collectionId);
        return $this->tags->suggest($collectionId, $fieldCode, $prefix, $limit, published: false);
    }

    public function getPage(ContentCollectionItemQuery $query): CollectionItemPageDTO
    {
        $this->guard->execute($query->collectionId);
        return $this->items->getPage($query);
    }

    public function getItem(int $collectionId, int $id): ?CollectionItemDTO
    {
        $this->guard->execute($collectionId);
        $item = $this->items->getItem($id);
        return $item?->collectionId === $collectionId ? $item : null;
    }

    public function addItem(CollectionItemFormDTO $item): int
    {
        $this->guard->execute($item->collectionId);
        return $this->write($item->collectionId, $item->values, true, fn (): int => $this->items->addItem($item, $this->user->id()));
    }

    public function updateItem(int $id, CollectionItemFormDTO $item): void
    {
        $this->requireItem($item->collectionId, $id);
        $this->write($item->collectionId, $item->values, false, fn () => $this->items->updateItem($id, $item, $this->user->id()));
    }

    public function patchItem(int $collectionId, int $id, CollectionItemPatchDTO $patch): void
    {
        $this->requireItem($collectionId, $id);
        $this->write($collectionId, $patch->values, false, fn () => $this->items->patchItem($id, $patch, $this->user->id()));
    }

    public function deleteItem(int $collectionId, int $id): void
    {
        $this->requireItem($collectionId, $id);
        $this->items->deleteItem($id);
    }

    public function findSection(int $collectionId, int $id): ?CollectionSectionDTO
    {
        $this->guard->execute($collectionId);
        return $this->sections->findSection($collectionId, $id);
    }

    /** @return list<CollectionSectionTreeNodeDTO> */
    public function getTree(int $collectionId): array
    {
        $this->guard->execute($collectionId);
        return $this->sections->getTree($collectionId);
    }

    public function addSection(CollectionSectionFormDTO $section): int
    {
        $this->guard->execute($section->collectionId);
        return $this->sections->addSection($section);
    }

    public function updateSection(int $id, CollectionSectionFormDTO $section): void
    {
        $this->requireSection($section->collectionId, $id);
        $this->sections->updateSection($id, $section);
    }

    public function previewSectionDeletion(int $collectionId, int $id): CollectionDeletionPreviewDTO
    {
        $this->requireSection($collectionId, $id);
        return $this->sections->previewDeletion($collectionId, $id);
    }

    public function deleteSection(int $collectionId, int $id, SectionDeletionMode $mode, ?int $targetId = null, ?string $expectedRevision = null): void
    {
        $this->requireSection($collectionId, $id);
        $this->sections->deleteSection($collectionId, $id, $mode, $targetId, $expectedRevision);
    }

    /** @template T
     * @param array<string, mixed> $values
     * @param callable(): T $operation
     * @return T
     */
    private function write(int $collectionId, array $values, bool $applyDefaults, callable $operation): mixed
    {
        return $this->transaction->run(function () use ($collectionId, $values, $applyDefaults, $operation): mixed {
            $this->collections->findForUpdate($collectionId);
            $this->relations->execute($collectionId, $values, $applyDefaults);
            return $operation();
        });
    }

    private function requireItem(int $collectionId, int $id): void
    {
        if ($this->getItem($collectionId, $id) === null) {
            throw new CollectionAccessDeniedException('The item does not belong to the authorized collection.');
        }
    }

    private function requireSection(int $collectionId, int $id): void
    {
        if ($this->findSection($collectionId, $id) === null) {
            throw new CollectionAccessDeniedException('The section does not belong to the authorized collection.');
        }
    }
}
