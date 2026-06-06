<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Johncms\Modules\Admin\Domain\Repository\DashboardRepositoryInterface;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Users\User;

final class EloquentDashboardRepository implements DashboardRepositoryInterface
{
    public function countActiveUsersSince(int $timestamp): int
    {
        return User::query()->where('lastdate', '>', $timestamp)->count();
    }

    public function countRegisteredUsersSince(int $timestamp): int
    {
        return User::query()->approved()->where('datereg', '>', $timestamp)->count();
    }

    public function countForumMessagesSince(int $timestamp): int
    {
        return ForumMessage::query()->where('date', '>', $timestamp)->count();
    }
}
