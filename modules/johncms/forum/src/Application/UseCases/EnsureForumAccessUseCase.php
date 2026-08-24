<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumErrorCode;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;

final readonly class EnsureForumAccessUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * @throws ForumAccessDeniedException
     */
    public function execute(): void
    {
        if ($this->accessChecker->allows(ForumPermissions::VIEW)) {
            return;
        }

        // A guest is told to sign in and everybody else that the forum is closed. Which of the
        // two it really is depends on what the user role holds, and asking that here would be a
        // query for the sake of the wording; signing in is the only thing a guest can act on.
        throw $this->currentUser->isGuest()
            ? new ForumAccessDeniedException(ForumErrorCode::FORUM_AUTH_REQUIRED, 'Forum is available for registered users only.')
            : new ForumAccessDeniedException(ForumErrorCode::FORUM_CLOSED, 'Forum is closed.');
    }
}
