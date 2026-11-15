<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;

interface GuestbookEntryRepositoryInterface
{
    /**
     * @return Collection<int, GuestbookEntry>
     */
    public function getEntries(bool $adminClub, int $limit, int $offset): Collection;

    public function countEntries(bool $adminClub): int;

    public function countEntriesSince(bool $adminClub, int $time): int;

    /** The entries shown above the given one in its guestbook: the newer ones. */
    public function countEntriesAbove(GuestbookEntry $entry): int;

    public function find(int $id): ?GuestbookEntry;

    /**
     * The entries that still exist of the given ones, by id.
     *
     * @param list<int> $ids
     * @return array<int, GuestbookEntry>
     */
    public function getByIds(array $ids): array;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): GuestbookEntry;

    public function save(GuestbookEntry $guestbookEntry): void;

    public function delete(GuestbookEntry $entry): void;

    /** The entry, its row held until the transaction ends; null when it is gone. */
    public function findLocked(int $id): ?GuestbookEntry;

    /**
     * @return list<int>
     */
    public function getIdsToClear(bool $adminClub, ?int $olderThan = null): array;

    /**
     * @param list<int> $ids
     */
    public function deleteByIds(array $ids): void;

    /**
     * The entries of the public guestbook written on each of the days starting at the given second.
     *
     * @return list<int> One number per day, oldest first.
     */
    public function countPostedByDay(int $start, int $days): array;
}
