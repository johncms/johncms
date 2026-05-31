<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

use Johncms\Users\User;

interface ProfileUserRepositoryInterface
{
    public function findById(int $id): ?User;

    /**
     * Mark the user's guestbook as read by syncing the seen counter with the current count.
     */
    public function markGuestbookSeen(int $userId, int $commCount): void;
}
