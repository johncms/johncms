<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Profile\Domain\Repository\IpHistoryRepositoryInterface;
use Johncms\Users\IpHistory;

final class EloquentIpHistoryRepository implements IpHistoryRepositoryInterface
{
    public function countByUser(int $userId): int
    {
        return IpHistory::query()->where('user_id', '=', $userId)->count();
    }

    /**
     * @return Collection<int, IpHistory>
     */
    public function getByUser(int $userId, int $limit, int $offset): Collection
    {
        return IpHistory::query()
            ->where('user_id', '=', $userId)
            ->orderByDesc('time')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }
}
