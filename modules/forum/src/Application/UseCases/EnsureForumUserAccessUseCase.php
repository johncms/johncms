<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumErrorCode;
use Johncms\Users\User;

final readonly class EnsureForumUserAccessUseCase
{
    public function __construct(
        private User $currentUser,
    ) {
    }

    public function execute(): void
    {
        if (! $this->currentUser->isValid()) {
            throw new ForumAccessDeniedException(ForumErrorCode::FORUM_AUTH_REQUIRED, 'Forum is available for registered users only.');
        }
    }
}
