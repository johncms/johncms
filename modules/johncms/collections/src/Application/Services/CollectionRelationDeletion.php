<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeStateDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionRelationImpactDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItemValue;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemValueRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

/** Called under the target collection/item locks; all cleanup shares the deletion transaction. */
final readonly class CollectionRelationDeletion
{
    public function __construct(
        private ContentCollectionItemValueRepositoryInterface $values,
        private ContentCollectionFieldRepositoryInterface $fields,
        private ContentCollectionItemRepositoryInterface $items,
        private ContentCollectionRepositoryInterface $collections,
    ) {
    }

    /** @param list<int> $ids
     * @param list<int> $deletedIds
     */
    public function impact(int $collectionId, array $ids, array $deletedIds, bool $forUpdate = false, ?int $deletedCollectionId = null): CollectionRelationImpactDTO
    {
        return $this->summarize(
            $this->values->getIncomingRelations($ids, $deletedIds, $forUpdate),
            $this->fields->getRelationsToCollection($collectionId, $forUpdate),
            $ids,
            $deletedCollectionId
        );
    }

    /** @param list<int> $ids
     * @param list<int> $deletedIds
     */
    public function remove(int $collectionId, array $ids, array $deletedIds, CollectionChangeDispatcher $changes, ?string $expected = null, ?int $deletedCollectionId = null): void
    {
        $rows = $this->values->getIncomingRelations($ids, $deletedIds, true);
        $impact = $this->summarize($rows, $this->fields->getRelationsToCollection($collectionId, true), $ids, $deletedCollectionId);
        if ($expected !== null && ! hash_equals($impact->revision, $expected) && ($expected !== '' || $impact->clearValues > 0)) {
            throw new CollectionDeletionException(['revision' => [d__('collections', 'The contents changed after confirmation was opened. Check the new counts and confirm again.')]]);
        }
        if ($impact->blockedValues > 0 || $impact->defaultFields > 0 || $impact->schemaFields > 0) {
            throw new CollectionDeletionException(['relations' => [d__('collections', 'Relations or field defaults prevent deletion. Remove the references or change their deletion policy first.')]]);
        }
        $sources = $rows->map(static fn ($row) => $row->item)->unique('id')->sortBy('id');
        foreach ($sources as $source) {
            $collection = $this->collections->findForUpdate($source->collection_id);
            if ($collection === null) {
                throw new CollectionDeletionException(['relations' => [d__('collections', 'The contents changed during deletion. Check the counts and try again.')]]);
            }
            $changes->ensureOwnerLoaded($collection->owner_module);
        }
        $this->values->deleteRelationValues($rows->pluck('id')->all());
        foreach ($sources as $source) {
            $this->items->update($source->id, ['updated_by' => null, 'updated_at' => new \DateTimeImmutable()]);
            $state = new CollectionChangeStateDTO($source->section_id, $source->active, $source->code);
            $changes->dispatch(new CollectionChangeDTO('item', 'saved', $source->collection_id, $source->id, $state, $state));
        }
    }

    /** @param Collection<int, ContentCollectionItemValue> $rows
     * @param Collection<int, ContentCollectionField> $fields
     * @param list<int> $ids
     */
    private function summarize(Collection $rows, Collection $fields, array $ids, ?int $deletedCollectionId): CollectionRelationImpactDTO
    {
        $clear = 0;
        $blocked = 0;
        $defaults = 0;
        $schema = 0;
        $evidence = [$ids, $deletedCollectionId];
        foreach ($rows as $row) {
            $field = $row->field;
            if (! $field->required && ($field->settings['on_delete'] ?? 'restrict') === 'clear') {
                ++$clear;
            } else {
                ++$blocked;
            }
            $evidence[] = [$row->getRawOriginal(), $field->getRawOriginal(), $row->item->getRawOriginal()];
        }
        foreach ($fields as $field) {
            if ($field->collection_id === $deletedCollectionId) {
                continue;
            }
            $default = $field->settings['default'] ?? [];
            if (array_intersect(array_map(strval(...), $ids), is_array($default) ? $default : [$default]) !== []) {
                ++$defaults;
            }
            if ($deletedCollectionId !== null) {
                ++$schema;
            }
            $evidence[] = $field->getRawOriginal();
        }
        return new CollectionRelationImpactDTO($clear, $blocked, $defaults, $schema, hash('sha256', serialize($evidence)));
    }
}
