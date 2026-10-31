<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Dashboard;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Checker\CheckProfile;
use Johncms\Checker\SystemCheckerInterface;
use Johncms\Modules\Admin\Application\Services\AdminPermissions;
use Johncms\View\Dashboard\AttentionItem;
use Johncms\View\Dashboard\AttentionLevel;
use Johncms\View\Dashboard\DashboardAttentionProviderInterface;

/**
 * Only the quick profile, for whoever may open diagnostics. The full page and active storage
 * probes are deliberately excluded: opening the dashboard must not run those checks.
 */
final readonly class SystemCheckAttentionProvider implements DashboardAttentionProviderInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private SystemCheckerInterface $checker,
    ) {
    }

    public function attentionItems(): iterable
    {
        if (! $this->accessChecker->allows(AdminPermissions::SYSTEM_CHECK)) {
            return;
        }

        $report = $this->checker->run(CheckProfile::Dashboard);

        if ($report->critical > 0) {
            yield new AttentionItem(d__('admin', 'Critical system checks failed'), '/admin/system_check', AttentionLevel::Danger, $report->critical, 'circle-alert');
        }

        if ($report->warnings > 0) {
            yield new AttentionItem(d__('admin', 'System check recommendations'), '/admin/system_check', AttentionLevel::Warning, $report->warnings, 'gauge');
        }

        if ($report->unavailable > 0) {
            yield new AttentionItem(d__('admin', 'Some system checks could not be completed'), '/admin/system_check', AttentionLevel::Warning, $report->unavailable, 'circle-alert');
        }
    }
}
