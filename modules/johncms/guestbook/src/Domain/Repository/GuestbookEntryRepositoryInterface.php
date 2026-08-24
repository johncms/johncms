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

    public function find(int $id): ?GuestbookEntry;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): GuestbookEntry;

    public function save(GuestbookEntry $guestbookEntry): void;

    public function delete(GuestbookEntry $entry): void;

    /**
     * @return Collection<int, GuestbookEntry>
     */
    public function getEntriesToClear(bool $adminClub, ?int $olderThan = null): Collection;

    public function deleteEntries(bool $adminClub, ?int $olderThan = null): void;
}
