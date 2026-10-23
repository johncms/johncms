<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Notifications;

use Johncms\Auth\CurrentUser;
use Johncms\Notifications\SystemNotification;
use Johncms\Notifications\SystemNotificationProviderInterface;

/**
 * The entries written into the personal guestbook of the visitor since they last looked at it.
 */
final readonly class GuestbookNotificationProvider implements SystemNotificationProviderInterface
{
    public function __construct(private CurrentUser $currentUser)
    {
    }

    public function notifications(): iterable
    {
        $user = $this->currentUser->user();
        $count = $user->comm_count - $user->comm_old;

        if ($count > 0) {
            yield new SystemNotification(
                'guestbook_comments',
                d__('profile', 'Guestbook'),
                '/profile/' . $this->currentUser->id() . '/guestbook',
                $count
            );
        }
    }
}
