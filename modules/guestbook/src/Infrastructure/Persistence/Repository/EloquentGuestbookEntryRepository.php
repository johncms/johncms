<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;

class EloquentGuestbookEntryRepository implements GuestbookEntryRepositoryInterface
{
    public function getGuestbookEntries(int $perPage = 20): LengthAwarePaginator
    {
        return GuestbookEntry::query()
            ->with('user')
            ->where('adm', 0)
            ->orderByDesc('time')
            ->paginate($perPage);
    }

    public function getAdminClubEntries(int $perPage = 20): LengthAwarePaginator
    {
        return GuestbookEntry::query()
            ->with('user')
            ->where('adm', 1)
            ->orderByDesc('time')
            ->paginate($perPage);
    }

    public function find(int $id): ?GuestbookEntry
    {
        return GuestbookEntry::query()->find($id);
    }

    public function save(GuestbookEntry $guestbookEntry): void
    {
        $guestbookEntry->save();
    }

    public function delete(GuestbookEntry $entry): void
    {
        $entry->delete();
    }
}
