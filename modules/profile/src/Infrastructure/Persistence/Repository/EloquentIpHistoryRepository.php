<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Profile\Domain\Repository\IpHistoryRepositoryInterface;
use Johncms\Users\IpHistory;

final class EloquentIpHistoryRepository implements IpHistoryRepositoryInterface
{
    public function paginateByUser(int $userId, int $perPage): LengthAwarePaginator
    {
        return IpHistory::query()
            ->where('user_id', '=', $userId)
            ->orderByDesc('time')
            ->paginate($perPage);
    }
}
