<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Notifications;

use Johncms\Auth\CurrentUser;
use Johncms\Notifications\SystemNotification;
use Johncms\Notifications\SystemNotificationProviderInterface;

/**
 * A ban the visitor is under, leading to the page that says what for and until when. A line
 * without a number, which still counts one in the badge of the header.
 */
final readonly class BanNotificationProvider implements SystemNotificationProviderInterface
{
    public function __construct(private CurrentUser $currentUser)
    {
    }

    public function notifications(): iterable
    {
        if ($this->currentUser->user()->ban === []) {
            return;
        }

        yield new SystemNotification(
            'ban',
            d__('profile', 'Ban'),
            '/profile/' . $this->currentUser->id() . '/bans',
            1,
            countShown: false
        );
    }
}
