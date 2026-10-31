<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Dashboard;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Admin\Domain\Services\ServerHealth;
use Johncms\Modules\Admin\Domain\Services\ServerStatusInterface;
use Johncms\Scheduler\ScheduleHeartbeatInterface;
use Johncms\View\Dashboard\AttentionItem;
use Johncms\View\Dashboard\AttentionLevel;
use Johncms\View\Dashboard\DashboardAttentionProviderInterface;

/**
 * The problems of the machine, for whoever manages the site: a scheduler that does not run — and
 * with it the mail queue, the cleanups and the tasks the panel queues — and a disk running out.
 */
final readonly class ServerAttentionProvider implements DashboardAttentionProviderInterface
{
    /** The system block of the dashboard: it shows the line of crontab and the way to the manual. */
    private const string SYSTEM_BLOCK = '/admin#admin-system-title';

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private ScheduleHeartbeatInterface $heartbeat,
        private ServerStatusInterface $server,
        private ServerHealth $health,
    ) {
    }

    public function attentionItems(): iterable
    {
        if (! $this->accessChecker->allows(CorePermissions::ADMIN_SETTINGS_MANAGE)) {
            return;
        }

        $lastRun = $this->heartbeat->lastBeat();

        if (! $this->health->schedulerRuns($lastRun, time())) {
            yield new AttentionItem(
                $lastRun === null
                    ? d__('admin', 'The task scheduler (cron) has never run')
                    : d__('admin', 'The task scheduler (cron) has stopped'),
                self::SYSTEM_BLOCK,
                AttentionLevel::Danger,
                icon: 'clock',
            );
        }

        if ($this->health->diskLow($this->server->disk())) {
            yield new AttentionItem(d__('admin', 'The disk is running out of space'), self::SYSTEM_BLOCK, AttentionLevel::Warning, icon: 'database');
        }
    }
}
