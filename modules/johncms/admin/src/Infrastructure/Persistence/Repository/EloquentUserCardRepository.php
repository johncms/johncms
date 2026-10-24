<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\Repository\UserCardRepositoryInterface;
use Johncms\Users\Ban;
use Johncms\Users\IpHistory;
use Johncms\Users\User;

final class EloquentUserCardRepository implements UserCardRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    /**
     * @return Collection<int, IpHistory>
     */
    public function getRecentIps(int $userId, int $limit): Collection
    {
        return IpHistory::query()
            ->where('user_id', $userId)
            ->orderByDesc('time')
            ->limit($limit)
            ->get();
    }

    public function countIps(int $userId): int
    {
        return IpHistory::query()->where('user_id', $userId)->count();
    }

    public function getActiveBans(int $userId, int $now): array
    {
        return Ban::query()
            ->where('user_id', $userId)
            ->where('ban_time', '>', $now)
            ->groupBy('ban_type')
            ->selectRaw('ban_type, MAX(ban_time) AS ends')
            ->pluck('ends', 'ban_type')
            ->map(static fn (mixed $end): int => (int) $end)
            ->all();
    }

    public function countBans(int $userId): int
    {
        return Ban::query()->where('user_id', $userId)->count();
    }
}
