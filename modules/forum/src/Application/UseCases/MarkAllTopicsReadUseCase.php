<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Domain\Repository\ForumUnreadRepositoryInterface;

final readonly class MarkAllTopicsReadUseCase
{
    public function __construct(
        private ForumUnreadRepositoryInterface $unreadRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(): void
    {
        $this->unreadRepository->markAllAsRead($this->currentUser->id());
    }
}
