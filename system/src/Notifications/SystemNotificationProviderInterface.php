<?php

declare(strict_types=1);

namespace Johncms\Notifications;

/**
 * How a module tells a signed-in visitor that something of it waits for them: letters not read, a
 * queue of moderation they look after, a ban.
 *
 * A module implements this, the container tags it, and SystemNotifications collects what every
 * provider yields: the notifications page lists it, the badge of the header adds the numbers up. A
 * module that is switched off has no provider, and nobody else has to know it exists.
 */
interface SystemNotificationProviderInterface
{
    /**
     * What waits for the current visitor, who is signed in whenever this is asked. Nothing when
     * nothing waits; a provider checks the permission its line needs itself.
     *
     * @return iterable<SystemNotification>
     */
    public function notifications(): iterable;
}
