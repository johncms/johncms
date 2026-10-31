<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Johncms\Database\Query\DailyCounts;
use Johncms\Modules\Admin\Domain\Repository\DashboardRepositoryInterface;
use Johncms\Users\User;

final class EloquentDashboardRepository implements DashboardRepositoryInterface
{
    public function countActiveUsersSince(int $timestamp): int
    {
        return User::query()->where('lastdate', '>', $timestamp)->count();
    }

    public function countRegisteredUsersByDay(int $start, int $days): array
    {
        return DailyCounts::count(User::query()->approved(), 'datereg', $start, $days);
    }
}
