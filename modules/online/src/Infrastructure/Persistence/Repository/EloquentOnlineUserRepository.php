<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Online\Domain\Repository\OnlineUserRepositoryInterface;
use Johncms\Users\User;

class EloquentOnlineUserRepository implements OnlineUserRepositoryInterface
{
    public function countOnline(): int
    {
        return User::query()
            ->where('lastdate', '>', (time() - 300))
            ->count();
    }

    public function getOnline(int $limit, int $offset): Collection
    {
        return User::query()
            ->where('lastdate', '>', (time() - 300))
            ->orderBy('lastdate', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function countHistory(): int
    {
        return User::query()
            ->whereBetween('lastdate', [(time() - 172800), (time() - 310)])
            ->count();
    }

    public function getHistory(int $limit, int $offset): Collection
    {
        return User::query()
            ->whereBetween('lastdate', [(time() - 172800), (time() - 310)])
            ->orderBy('lastdate', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }
}
