<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Notifications;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Admin\Domain\Repository\RegistrationModerationRepositoryInterface;
use Johncms\Notifications\SystemNotification;
use Johncms\Notifications\SystemNotificationProviderInterface;

/**
 * The accounts waiting for an administrator to let them in, for whoever may open the panel.
 */
final readonly class PendingRegistrationsNotificationProvider implements SystemNotificationProviderInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private RegistrationModerationRepositoryInterface $registrations,
    ) {
    }

    public function notifications(): iterable
    {
        if (! $this->accessChecker->allows(CorePermissions::ADMIN_ACCESS)) {
            return;
        }

        $count = $this->registrations->countPending();

        if ($count > 0) {
            yield new SystemNotification('reg_total', d__('admin', 'Users on registration'), '/admin/reg/', $count);
        }
    }
}
