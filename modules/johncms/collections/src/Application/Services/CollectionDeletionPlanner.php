<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeStateDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionDeletionPlanDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionDeletionPreviewDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Services\SectionTree;

final readonly class CollectionDeletionPlanner
{
    public function __construct(
        private ContentCollectionRepositoryInterface $collections,
        private ContentCollectionSectionRepositoryInterface $sections,
        private ContentCollectionItemRepositoryInterface $items,
        private ContentCollectionFieldRepositoryInterface $fields,
        private CollectionRelationDeletion $relations,
    ) {
    }

    /** A locking plan must be built inside the caller's transaction. */
    public function plan(int $collectionId, ?int $sectionId = null, bool $forUpdate = false): CollectionDeletionPlanDTO
    {
        $collection = $forUpdate ? $this->collections->findForUpdate($collectionId) : $this->collections->findById($collectionId);
        if ($collection === null) {
            throw new CollectionDeletionException(['collection_id' => [d__('collections', 'Collection not found.')]]);
        }
        $sections = ($forUpdate ? $this->sections->getAllForUpdate($collectionId) : $this->sections->getAllByCollection($collectionId))->keyBy('id');
        if ($sectionId !== null && ! isset($sections[$sectionId])) {
            throw new CollectionDeletionException(['id' => [d__('collections', 'The section does not belong to this collection or no longer exists.')]]);
        }
        $tree = SectionTree::of($sections);
        $sectionIds = $sectionId === null ? $tree->ordered() : $tree->subtreeOf($sectionId);
        $affectedSections = $sections->only($sectionIds);
        $items = $this->items->getForDeletion($collectionId, $sectionId === null ? null : $sectionIds, $forUpdate);
        $fields = $sectionId !== null ? new Collection() : ($forUpdate ? $this->fields->getForUpdate($collectionId) : $this->fields->getByCollection($collectionId));
        if ($forUpdate && ($this->sections->hasForeignChildrenForUpdate($collectionId, $sectionIds) || $this->items->hasForeignItemsInSectionsForUpdate($collectionId, $sectionIds))) {
            throw new CollectionDeletionException(['tree' => [d__('collections', 'Sections are referenced by another collection. Fix these links before deleting.')]]);
        }
        $relationImpact = $this->relations->impact($collectionId, $items->pluck('id')->all(), $items->pluck('id')->all(), $forUpdate, $sectionId === null ? $collectionId : null);
        $revision = hash('sha256', serialize([
            $relationImpact->revision,
            $collection->getRawOriginal(), $this->attributes($affectedSections), $this->attributes($items), $this->attributes($fields),
        ]));
        return new CollectionDeletionPlanDTO(
            new CollectionDeletionPreviewDTO(
                $collectionId,
                $sectionId,
                $sectionId === null ? $collection->name : $sections[$sectionId]->name,
                count($sectionIds),
                $items->count(),
                $fields->count(),
                $revision,
                $collection->owner_module,
                $relationImpact
            ),
            $sectionIds,
            $items->pluck('id')->all(),
            $fields->pluck('id')->all(),
            $affectedSections->keyBy('id')->map(static fn ($section) => new CollectionChangeStateDTO($section->parent, $section->active, $section->code))->all(),
            $fields->keyBy('id')->map(static fn ($field) => new CollectionChangeStateDTO(null, null, $field->code))->all(),
            new CollectionChangeStateDTO(null, $collection->active, $collection->code)
        );
    }

    public function ensureRevision(CollectionDeletionPreviewDTO $preview, ?string $expected): void
    {
        if ($expected !== null && ! hash_equals($preview->revision, $expected)) {
            throw new CollectionDeletionException(['revision' => [d__('collections', 'The contents changed after confirmation was opened. Check the new counts and confirm again.')]]);
        }
    }

    /** @template T of Model
     * @param Collection<int, T> $models
     * @return list<array<string, mixed>>
     */
    private function attributes(Collection $models): array
    {
        return $models->sortBy('id')->values()->map(static fn ($model): array => $model->getRawOriginal())->all();
    }
}
