<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface
{
    public function getPaginated(int $page, int $perPage): LengthAwarePaginator;

    public function markAsRead(array $ids): void;

    public function clearAllForUser(int $userId): void;
}
