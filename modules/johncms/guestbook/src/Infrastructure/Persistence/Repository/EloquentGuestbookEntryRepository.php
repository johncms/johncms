<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;

class EloquentGuestbookEntryRepository implements GuestbookEntryRepositoryInterface
{
    public function getEntries(bool $adminClub, int $limit, int $offset): Collection
    {
        return GuestbookEntry::query()
            ->with('user')
            ->where('adm', $adminClub ? 1 : 0)
            ->orderByDesc('time')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function countEntries(bool $adminClub): int
    {
        return GuestbookEntry::query()
            ->where('adm', $adminClub ? 1 : 0)
            ->count();
    }

    public function find(int $id): ?GuestbookEntry
    {
        return GuestbookEntry::query()->find($id);
    }

    public function create(array $attributes): GuestbookEntry
    {
        return GuestbookEntry::query()->create($attributes);
    }

    public function save(GuestbookEntry $guestbookEntry): void
    {
        $guestbookEntry->save();
    }

    public function delete(GuestbookEntry $entry): void
    {
        $entry->delete();
    }

    public function getEntriesToClear(bool $adminClub, ?int $olderThan = null): Collection
    {
        return $this->entriesQuery($adminClub, $olderThan)->get();
    }

    public function deleteEntries(bool $adminClub, ?int $olderThan = null): void
    {
        $this->entriesQuery($adminClub, $olderThan)->delete();
    }

    /**
     * @return Builder<GuestbookEntry>
     */
    private function entriesQuery(bool $adminClub, ?int $olderThan): Builder
    {
        return GuestbookEntry::query()
            ->where('adm', $adminClub ? 1 : 0)
            ->when($olderThan !== null, fn (Builder $query) => $query->where('time', '<', $olderThan));
    }
}
