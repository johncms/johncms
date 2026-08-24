<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Online\Domain\Repository\OnlineGuestRepositoryInterface;
use Johncms\Users\GuestSession;

class EloquentOnlineGuestRepository implements OnlineGuestRepositoryInterface
{
    public function countOnline(): int
    {
        return GuestSession::query()
            ->where('lastdate', '>', (time() - 300))
            ->count();
    }

    public function getOnline(int $limit, int $offset): Collection
    {
        return GuestSession::query()
            ->where('lastdate', '>', (time() - 300))
            ->orderBy('lastdate', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }
}
