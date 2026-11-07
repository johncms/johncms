<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use DateTimeImmutable;
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
        if ($impact->isBlocked()) {
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
            $this->items->update($source->id, ['updated_by' => null, 'updated_at' => new DateTimeImmutable()]);
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
        $blockers = [];
        $schemaBlockers = [];
        $evidence = [$ids, $deletedCollectionId];
        foreach ($rows as $row) {
            $field = $row->field;
            if (! $field->required && ($field->settings['on_delete'] ?? 'restrict') === 'clear') {
                ++$clear;
            } else {
                ++$blocked;
                $blockers[] = ['target' => (int) $row->value_int, 'field' => $field->name, 'source' => $row->item->name];
            }
            $source = $row->item->getRawOriginal();
            unset($source['view_count']);
            $evidence[] = [$row->getRawOriginal(), $field->getRawOriginal(), $source];
        }
        foreach ($fields as $field) {
            if ($field->collection_id === $deletedCollectionId) {
                continue;
            }
            $default = $field->settings['default'] ?? [];
            $held = array_intersect(array_map(strval(...), $ids), is_array($default) ? $default : [$default]);
            if ($held !== []) {
                ++$defaults;
                foreach ($held as $target) {
                    $blockers[] = ['target' => (int) $target, 'field' => $field->name, 'source' => null];
                }
            }
            if ($deletedCollectionId !== null) {
                ++$schema;
                $schemaBlockers[] = ['field' => $field->name, 'collection' => $field->collection->name];
            }
            $evidence[] = $field->getRawOriginal();
        }
        $names = $this->items->getNames(array_values(array_unique(array_column($blockers, 'target'))));
        foreach ($blockers as $key => $blocker) {
            $blockers[$key]['name'] = $names[$blocker['target']] ?? '#' . $blocker['target'];
        }
        return new CollectionRelationImpactDTO($clear, $blocked, $defaults, $schema, hash('sha256', serialize($evidence)), $blockers, $schemaBlockers);
    }
}
