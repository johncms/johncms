<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Users\User;

final readonly class EnsureNewTopicAccessUseCase
{
    public function __construct(
        private User $currentUser,
    ) {
    }

    public function execute(): void
    {
        $config = config('johncms');

        if (
            ! $this->currentUser->is_valid
            || isset($this->currentUser->ban['1'])
            || isset($this->currentUser->ban['11'])
            || (! $this->currentUser->rights && $config['mod_forum'] === 3)
        ) {
            throw new AccessDeniedException('Access denied to create topic.');
        }
    }
}
