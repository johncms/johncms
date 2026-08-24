<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Infrastructure\Persistence\Repository;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Notifications\Domain\Repository\NotificationRepositoryInterface;
use Johncms\Notifications\Notification;

final class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function countNotifications(): int
    {
        return Notification::query()->count();
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(int $limit, int $offset): Collection
    {
        return Notification::query()
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function markAsRead(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        Notification::query()
            ->whereIn('id', $ids)
            ->unread()
            ->update(['read_at' => Carbon::now()]);
    }

    public function clearAllForUser(int $userId): void
    {
        Notification::query()->where('user_id', $userId)->delete();
    }
}
