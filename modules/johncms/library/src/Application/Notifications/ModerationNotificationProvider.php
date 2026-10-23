<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Notifications;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Modules\Library\Domain\Repository\LibraryTextRepositoryInterface;
use Johncms\Notifications\SystemNotification;
use Johncms\Notifications\SystemNotificationProviderInterface;

/**
 * The articles waiting for moderation, for the moderator of the library — the queue is theirs.
 */
final readonly class ModerationNotificationProvider implements SystemNotificationProviderInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private LibraryTextRepositoryInterface $texts,
    ) {
    }

    public function notifications(): iterable
    {
        if (! $this->accessChecker->allows(LibraryPermissions::MODERATE)) {
            return;
        }

        $count = $this->texts->countAwaitingModeration();

        if ($count > 0) {
            yield new SystemNotification('library_mod', d__('library', 'Articles on moderation'), '/library/premod', $count);
        }
    }
}
