<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Users\User;

final readonly class EnsureDeleteTopicAccessUseCase
{
    public function __construct(
        private User $currentUser,
    ) {
    }

    public function execute(): void
    {
        if (! ($this->currentUser->rights === 3 || $this->currentUser->rights >= 6)) {
            throw new ForumAccessDeniedException('Access denied to delete topic.');
        }
    }
}
