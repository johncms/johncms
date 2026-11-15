<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Database\Query\DailyCounts;
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
            ->orderByDesc('id')
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

    public function countEntriesSince(bool $adminClub, int $time): int
    {
        return GuestbookEntry::query()
            ->where('adm', $adminClub ? 1 : 0)
            ->where('time', '>', $time)
            ->count();
    }

    public function countEntriesAbove(GuestbookEntry $entry): int
    {
        // The feed puts the newer entries first, and of two written in the same second the later one.
        $time = (int) $entry->getRawOriginal('time');

        return GuestbookEntry::query()
            ->where('adm', $entry->adm ? 1 : 0)
            ->where(
                fn (Builder $query) => $query
                    ->where('time', '>', $time)
                    ->orWhere(fn (Builder $same) => $same->where('time', $time)->where('id', '>', $entry->id))
            )
            ->count();
    }

    public function find(int $id): ?GuestbookEntry
    {
        return GuestbookEntry::query()->find($id);
    }

    public function getByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return GuestbookEntry::query()->with('user')->whereKey($ids)->get()->keyBy('id')->all();
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

    public function findLocked(int $id): ?GuestbookEntry
    {
        $query = GuestbookEntry::query()->whereKey($id);
        $query->getQuery()->lockForUpdate();

        return $query->first();
    }

    public function getIdsToClear(bool $adminClub, ?int $olderThan = null): array
    {
        return array_map(intval(...), $this->entriesQuery($adminClub, $olderThan)->orderBy('id')->pluck('id')->all());
    }

    public function deleteByIds(array $ids): void
    {
        GuestbookEntry::query()->whereKey($ids)->delete();
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

    public function countPostedByDay(int $start, int $days): array
    {
        return DailyCounts::count(GuestbookEntry::query()->where('adm', 0), 'time', $start, $days);
    }
}
