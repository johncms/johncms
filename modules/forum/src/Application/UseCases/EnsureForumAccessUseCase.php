<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumErrorCode;
use Johncms\Users\User;

final readonly class EnsureForumAccessUseCase
{
    public function __construct(
        private User $currentUser,
    ) {
    }

    /**
     * @throws ForumAccessDeniedException
     */
    public function execute(): void
    {
        $config = config('johncms');

        if (! $config['mod_forum'] && $this->currentUser->rights < 7) {
            throw new ForumAccessDeniedException(ForumErrorCode::FORUM_CLOSED, 'Forum is closed.');
        }

        if ($config['mod_forum'] === 1 && ! $this->currentUser->isValid()) {
            throw new ForumAccessDeniedException(ForumErrorCode::FORUM_AUTH_REQUIRED, 'Forum is available for registered users only.');
        }
    }
}
