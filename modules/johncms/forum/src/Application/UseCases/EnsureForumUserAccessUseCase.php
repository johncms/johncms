<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumErrorCode;

final readonly class EnsureForumUserAccessUseCase
{
    public function __construct(
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(): void
    {
        if (! $this->currentUser->isValid()) {
            throw new ForumAccessDeniedException(ForumErrorCode::FORUM_AUTH_REQUIRED, 'Forum is available for registered users only.');
        }
    }
}
