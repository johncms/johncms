<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\Repository\RegistrationModerationRepositoryInterface;
use Johncms\Users\IpHistory;
use Johncms\Users\User;

final class EloquentRegistrationModerationRepository implements RegistrationModerationRepositoryInterface
{
    public function countPending(): int
    {
        return User::query()->where('preg', 0)->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function getPending(int $limit, int $offset): Collection
    {
        return User::query()
            ->where('preg', 0)
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function approve(int $id, string $adminName): void
    {
        User::query()
            ->where('id', $id)
            ->update(['preg' => 1, 'regadm' => $adminName]);
    }

    public function approveAll(string $adminName): void
    {
        User::query()
            ->where('preg', 0)
            ->update(['preg' => 1, 'regadm' => $adminName]);
    }

    public function delete(int $id): void
    {
        $deleted = User::query()
            ->where('id', $id)
            ->where('preg', 0)
            ->delete();

        if ($deleted > 0) {
            IpHistory::query()->where('user_id', $id)->delete();
        }
    }

    public function deleteAll(): void
    {
        $ids = User::query()->where('preg', 0)->pluck('id')->all();
        if ($ids === []) {
            return;
        }

        IpHistory::query()->whereIn('user_id', $ids)->delete();
        User::query()->whereIn('id', $ids)->delete();
    }

    public function deleteByIp(int $ip): void
    {
        $ids = User::query()
            ->where('preg', 0)
            ->where('ip', $ip)
            ->pluck('id')
            ->all();

        if ($ids === []) {
            return;
        }

        IpHistory::query()->whereIn('user_id', $ids)->delete();
        User::query()->whereIn('id', $ids)->delete();
    }
}
