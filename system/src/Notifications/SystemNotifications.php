<?php

declare(strict_types=1);

namespace Johncms\Notifications;

use Johncms\Auth\CurrentUser;

/**
 * Everything that waits for the signed-in visitor, gathered from the modules
 * (SystemNotificationProviderInterface). A guest is waited for by nothing.
 */
final readonly class SystemNotifications
{
    /**
     * @param iterable<SystemNotificationProviderInterface> $providers
     */
    public function __construct(
        private CurrentUser $currentUser,
        private iterable $providers,
    ) {
    }

    /**
     * @return list<SystemNotification>
     */
    public function all(): array
    {
        if (! $this->currentUser->isValid()) {
            return [];
        }

        $notifications = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->notifications() as $notification) {
                $notifications[] = $notification;
            }
        }

        return $notifications;
    }

    /**
     * The numbers by key, the unread personal notifications under "notifications", and the sum of
     * them all under "all" — what the notifications() function of the templates returns.
     *
     * @return array<string, int>
     */
    public function counters(): array
    {
        if (! $this->currentUser->isValid()) {
            return [];
        }

        $counters = [];

        foreach ($this->all() as $notification) {
            $counters[$notification->key] = $notification->count;
        }

        $counters['notifications'] = (new Notification())->unread()->count();
        $counters['all'] = array_sum($counters);

        return $counters;
    }
}
