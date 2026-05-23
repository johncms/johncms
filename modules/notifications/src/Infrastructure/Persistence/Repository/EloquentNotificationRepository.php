<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Infrastructure\Persistence\Repository;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Notifications\Domain\Repository\NotificationRepositoryInterface;
use Johncms\Notifications\Notification;

final class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function getPaginated(int $page, int $perPage): LengthAwarePaginator
    {
        return Notification::query()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
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
