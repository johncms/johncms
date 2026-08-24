<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;

final readonly class MarkGuestbookReadUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * Reset the unread guestbook counter when the owner views their own guestbook.
     */
    public function execute(int $profileUserId): void
    {
        if ($this->currentUser->id() !== $profileUserId) {
            return;
        }

        if ($this->currentUser->user()->comm_count === $this->currentUser->user()->comm_old) {
            return;
        }

        $this->profileUserRepository->markGuestbookSeen($this->currentUser->id(), $this->currentUser->user()->comm_count);
    }
}
