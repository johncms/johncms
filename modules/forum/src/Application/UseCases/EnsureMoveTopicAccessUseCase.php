<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;

final readonly class EnsureMoveTopicAccessUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function execute(): void
    {
        if (! $this->accessChecker->allows(ForumPermissions::TOPIC_MODERATE)) {
            throw new ForumAccessDeniedException('Access denied to move topic.');
        }
    }
}
