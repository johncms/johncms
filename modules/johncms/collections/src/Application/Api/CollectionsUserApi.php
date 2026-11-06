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
        private \Johncms\Modules\Collections\Application\UseCases\EnsureCollectionItemWriteAccessUseCase $writes,
        private \Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface $sectionRepository,
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
        return $this->write($item->collectionId, $item->values, true, function () use ($item): int {
            $this->writes->execute($item->collectionId, null, $this->publication($item));
            return $this->items->addItem($item, $this->user->id());
        });
    }

    public function updateItem(int $id, CollectionItemFormDTO $item): void
    {
        $this->requireItem($item->collectionId, $id);
        $this->write($item->collectionId, $item->values, false, function () use ($id, $item): void {
            $this->writes->execute($item->collectionId, $id, $this->publication($item));
            $this->items->updateItem($id, $item, $this->user->id());
        });
    }

    public function patchItem(int $collectionId, int $id, CollectionItemPatchDTO $patch): void
    {
        $this->requireItem($collectionId, $id);
        $this->write($collectionId, $patch->values, false, function () use ($collectionId, $id, $patch): void {
            $this->writes->execute($collectionId, $id, $patch->attributes);
            $this->items->patchItem($id, $patch, $this->user->id());
        });
    }

    public function previewItemDeletion(int $collectionId, int $id): \Johncms\Modules\Collections\Application\DTO\CollectionRelationImpactDTO
    {
        $this->requireItem($collectionId, $id);
        $this->guard->executeAction($collectionId, 'delete');
        return $this->items->previewItemDeletion($id);
    }

    public function deleteItem(int $collectionId, int $id, ?string $expectedRelations = null): void
    {
        $this->requireItem($collectionId, $id);
        $this->write($collectionId, [], false, function () use ($collectionId, $id, $expectedRelations): void {
            $this->guard->executeAction($collectionId, 'delete');
            $this->requireItem($collectionId, $id);
            $this->items->deleteItem($id, $expectedRelations);
        });
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
        return $this->sectionWrite($section->collectionId, function () use ($section): int {
            if ($section->active) {
                $this->guard->executeAction($section->collectionId, 'publish');
            }
            return $this->sections->addSection($section);
        });
    }

    public function updateSection(int $id, CollectionSectionFormDTO $section): void
    {
        $this->requireSection($section->collectionId, $id);
        $this->sectionWrite($section->collectionId, function () use ($id, $section): void {
            $current = $this->sectionRepository->findForUpdate($id);
            if ($current === null || $current->collection_id !== $section->collectionId) {
                throw new CollectionAccessDeniedException('The section does not belong to the authorized collection.');
            }
            if ($current->active !== $section->active || ($current->active && $current->parent !== $section->parent)) {
                $this->guard->executeAction($section->collectionId, 'publish');
            }
            $this->sections->updateSection($id, $section);
        });
    }

    public function previewSectionDeletion(int $collectionId, int $id): CollectionDeletionPreviewDTO
    {
        $this->requireSection($collectionId, $id);
        $this->guard->executeAction($collectionId, 'sections');
        return $this->sections->previewDeletion($collectionId, $id);
    }

    public function deleteSection(int $collectionId, int $id, SectionDeletionMode $mode, ?int $targetId = null, ?string $expectedRevision = null): void
    {
        $this->requireSection($collectionId, $id);
        $this->sectionWrite($collectionId, function () use ($collectionId, $id, $mode, $targetId, $expectedRevision): void {
            $this->guard->executeAction($collectionId, $mode === SectionDeletionMode::DeleteItems ? 'delete' : 'edit');
            if ($mode === SectionDeletionMode::MoveItems) {
                $this->guard->executeAction($collectionId, 'publish');
            }
            $this->sections->deleteSection($collectionId, $id, $mode, $targetId, $expectedRevision);
        });
    }

    /** @return array<string, mixed> */
    private function publication(CollectionItemFormDTO $item): array
    {
        return ['active' => $item->active, 'activeFrom' => $item->activeFrom, 'activeTo' => $item->activeTo, 'sectionId' => $item->sectionId];
    }

    /** @template T
     * @param callable(): T $operation
     * @return T
     */
    private function sectionWrite(int $collectionId, callable $operation): mixed
    {
        return $this->write($collectionId, [], false, function () use ($collectionId, $operation): mixed {
            $this->guard->executeAction($collectionId, 'sections');
            return $operation();
        });
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
