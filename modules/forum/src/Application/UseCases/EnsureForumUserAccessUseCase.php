<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumAuthRequiredException;
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
            throw new ForumAuthRequiredException('Forum is available for registered users only.');
        }
    }
}
