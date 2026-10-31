<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Dashboard;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Checker\SystemChecker;
use Johncms\Modules\Admin\Application\Services\AdminPermissions;
use Johncms\View\Dashboard\AttentionItem;
use Johncms\View\Dashboard\AttentionLevel;
use Johncms\View\Dashboard\DashboardAttentionProviderInterface;

/**
 * The checks of PHP that fail, for whoever may open the system check: the extensions the site does
 * not work without, and the recommendations that cost it speed. The database server is left to the
 * page of the check: asking it runs queries a dashboard has no business running on every visit, and
 * the installer already refused a server that does not fit. The notes of the security section are
 * only to know and stay there as well.
 */
final readonly class SystemCheckAttentionProvider implements DashboardAttentionProviderInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private SystemChecker $checker,
    ) {
    }

    public function attentionItems(): iterable
    {
        if (! $this->accessChecker->allows(AdminPermissions::SYSTEM_CHECK)) {
            return;
        }

        $failed = [SystemChecker::CRITICAL => 0, SystemChecker::WARNING => 0];
        $checks = [...$this->checker->checkExtensions(), ...$this->checker->recommendations()];

        foreach ($checks as $check) {
            $level = $check['error_level'] ?? SystemChecker::CRITICAL;

            if ($check['error'] && isset($failed[$level])) {
                $failed[$level]++;
            }
        }

        if ($failed[SystemChecker::CRITICAL] > 0) {
            yield new AttentionItem(d__('admin', 'PHP does not meet the requirements of the site'), '/admin/system_check', AttentionLevel::Danger, $failed[SystemChecker::CRITICAL], 'circle-alert');
        }

        if ($failed[SystemChecker::WARNING] > 0) {
            yield new AttentionItem(d__('admin', 'Recommendations to speed up PHP'), '/admin/system_check', AttentionLevel::Warning, $failed[SystemChecker::WARNING], 'gauge');
        }
    }
}
