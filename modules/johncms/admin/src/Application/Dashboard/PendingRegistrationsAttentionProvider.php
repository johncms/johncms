<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Dashboard;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Admin\Domain\Repository\RegistrationModerationRepositoryInterface;
use Johncms\View\Dashboard\AttentionItem;
use Johncms\View\Dashboard\AttentionLevel;
use Johncms\View\Dashboard\DashboardAttentionProviderInterface;

/**
 * The accounts waiting for an administrator to let them in.
 */
final readonly class PendingRegistrationsAttentionProvider implements DashboardAttentionProviderInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private RegistrationModerationRepositoryInterface $registrations,
    ) {
    }

    public function attentionItems(): iterable
    {
        if (! $this->accessChecker->allows(CorePermissions::ADMIN_ACCESS)) {
            return;
        }

        $count = $this->registrations->countPending();

        if ($count > 0) {
            yield new AttentionItem(d__('admin', 'Users on registration'), '/admin/users?status=pending', AttentionLevel::Pending, $count, 'user-plus');
        }
    }
}
