<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Notifications\Notification;

interface NotificationRepositoryInterface
{
    public function countNotifications(): int;

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(int $limit, int $offset): Collection;

    public function markAsRead(array $ids): void;

    public function clearAllForUser(int $userId): void;
}
