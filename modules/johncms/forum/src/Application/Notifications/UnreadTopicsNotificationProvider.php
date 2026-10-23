<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Notifications;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Notifications\SystemNotification;
use Johncms\Notifications\SystemNotificationProviderInterface;

/**
 * The topics with posts the visitor has not read, for a visitor who asked to be told about them in
 * the settings of the notifications.
 */
final readonly class UnreadTopicsNotificationProvider implements SystemNotificationProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
        private ForumTopicRepositoryInterface $topics,
    ) {
    }

    public function notifications(): iterable
    {
        $settings = $this->currentUser->user()->notification_settings ?? [];

        if (empty($settings['show_forum_unread'])) {
            return;
        }

        $count = $this->topics->countUnreadForUser(
            $this->currentUser->id(),
            $this->accessChecker->allows(ForumPermissions::DELETED_VIEW)
        );

        if ($count > 0) {
            yield new SystemNotification('forum_new', d__('forum', 'New forum posts'), '/forum/?act=new', $count);
        }
    }
}
