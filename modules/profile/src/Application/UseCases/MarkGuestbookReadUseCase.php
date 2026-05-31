<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class MarkGuestbookReadUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private User $currentUser,
    ) {
    }

    /**
     * Reset the unread guestbook counter when the owner views their own guestbook.
     */
    public function execute(int $profileUserId): void
    {
        if ($this->currentUser->id !== $profileUserId) {
            return;
        }

        if ($this->currentUser->comm_count === $this->currentUser->comm_old) {
            return;
        }

        $this->profileUserRepository->markGuestbookSeen($this->currentUser->id, $this->currentUser->comm_count);
    }
}
