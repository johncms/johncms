<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\UseCases;

use Johncms\Modules\Notifications\Domain\Repository\NotificationRepositoryInterface;
use Johncms\Users\User;

final readonly class ClearNotificationsUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private User $currentUser,
    ) {
    }

    public function execute(): void
    {
        $this->notificationRepository->clearAllForUser($this->currentUser->id);
    }
}
