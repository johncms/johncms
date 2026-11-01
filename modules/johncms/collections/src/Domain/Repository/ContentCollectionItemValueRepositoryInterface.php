<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Repository;

interface ContentCollectionItemValueRepositoryInterface
{
    /**
     * Remove all custom field values of an item.
     */
    public function deleteByItem(int $itemId): void;

    /** @param list<int> $fieldIds */
    public function deleteFields(int $itemId, array $fieldIds): void;

    /**
     * Bulk insert prepared value rows (raw typed columns).
     *
     * @param list<array<string, mixed>> $rows
     */
    public function insertMany(array $rows): void;
}
