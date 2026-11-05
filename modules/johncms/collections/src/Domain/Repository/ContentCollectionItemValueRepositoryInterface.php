<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Repository;

interface ContentCollectionItemValueRepositoryInterface
{
    /**
     * Remove all custom field values of an item.
     */
    public function deleteByItem(int $itemId): void;

    /** @param list<int> $targetIds
     * @param list<int> $deletedItemIds
     * Current locking read; references inside the same deletion are ignored.
     */
    public function hasIncomingRelationsForUpdate(array $targetIds, array $deletedItemIds): bool;

    /** @param list<int> $targetIds
     * @param list<int> $deletedItemIds
     * @return \Illuminate\Database\Eloquent\Collection<int, \Johncms\Modules\Collections\Domain\Models\ContentCollectionItemValue>
     */
    public function getIncomingRelations(array $targetIds, array $deletedItemIds, bool $forUpdate = false): \Illuminate\Database\Eloquent\Collection;

    /** @param list<int> $ids */
    public function deleteRelationValues(array $ids): void;

    /** @param list<int> $fieldIds */
    public function deleteFields(int $itemId, array $fieldIds): void;

    /**
     * Bulk insert prepared value rows (raw typed columns).
     *
     * @param list<array<string, mixed>> $rows
     */
    public function insertMany(array $rows): void;
}
