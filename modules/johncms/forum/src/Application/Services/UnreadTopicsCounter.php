<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

/**
 * The topics with posts the current visitor has not read: the deleted ones only for whoever may see
 * them, none for a guest, who has read nothing and is told nothing.
 */
final readonly class UnreadTopicsCounter
{
    public function __construct(
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
        private ForumTopicRepositoryInterface $topics,
    ) {
    }

    public function count(): int
    {
        if (! $this->currentUser->isValid()) {
            return 0;
        }

        return $this->topics->countUnreadForUser(
            $this->currentUser->id(),
            $this->accessChecker->allows(ForumPermissions::DELETED_VIEW)
        );
    }
}
