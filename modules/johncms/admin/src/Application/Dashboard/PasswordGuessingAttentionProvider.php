<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Dashboard;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Admin\Application\Services\AdminPermissions;
use Johncms\Modules\Admin\Domain\Repository\SecurityOverviewRepositoryInterface;
use Johncms\View\Dashboard\AttentionItem;
use Johncms\View\Dashboard\AttentionLevel;
use Johncms\View\Dashboard\DashboardAttentionProviderInterface;

/**
 * The addresses that keep failing to sign in — somebody guessing passwords, most likely — for
 * whoever may read the sign-in log, which is where the line leads.
 */
final readonly class PasswordGuessingAttentionProvider implements DashboardAttentionProviderInterface
{
    private const int DAY = 86400;

    /** More than a person mistyping a password: a few slips a day are nothing to look at. */
    private const int ATTEMPTS = 10;

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private SecurityOverviewRepositoryInterface $repository,
    ) {
    }

    public function attentionItems(): iterable
    {
        if (! $this->accessChecker->allows(AdminPermissions::AUTH_LOG_VIEW)) {
            return;
        }

        $count = $this->repository->countIpsWithFailedSignInsSince(time() - self::DAY, self::ATTEMPTS);

        if ($count > 0) {
            yield new AttentionItem(
                d__('admin', 'Addresses that may be guessing passwords'),
                '/admin/auth-log?' . http_build_query(['event' => 'login.failed', 'period' => 'day']),
                AttentionLevel::Warning,
                $count,
                'shield',
            );
        }
    }
}
