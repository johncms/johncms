<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Notifications\Domain\Repository\NotificationRepositoryInterface;

final readonly class ClearNotificationsUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(): void
    {
        $this->notificationRepository->clearAllForUser($this->currentUser->id());
    }
}
