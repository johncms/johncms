<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Repository\ForumUnreadRepositoryInterface;
use Johncms\Users\User;

final readonly class MarkAllTopicsReadUseCase
{
    public function __construct(
        private ForumUnreadRepositoryInterface $unreadRepository,
        private User $currentUser,
    ) {
    }

    public function execute(): void
    {
        $this->unreadRepository->markAllAsRead((int) $this->currentUser->id);
    }
}
