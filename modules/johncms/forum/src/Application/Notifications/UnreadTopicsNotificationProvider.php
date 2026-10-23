<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Notifications;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Services\UnreadTopicsCounter;
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
        private UnreadTopicsCounter $unreadTopics,
    ) {
    }

    public function notifications(): iterable
    {
        $settings = $this->currentUser->user()->notification_settings ?? [];

        if (empty($settings['show_forum_unread'])) {
            return;
        }

        $count = $this->unreadTopics->count();

        if ($count > 0) {
            yield new SystemNotification('forum_new', d__('forum', 'New forum posts'), '/forum/?act=new', $count);
        }
    }
}
