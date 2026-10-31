<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Admin\Application\DTO\SystemOverviewDTO;
use Johncms\Modules\Admin\Domain\Services\ServerHealth;
use Johncms\Modules\Admin\Domain\Services\ServerStatusInterface;
use Johncms\Scheduler\ScheduleHeartbeatInterface;

/**
 * The state of the system for whoever manages the site: the one who can fix what it shows. The
 * versions of the server are no business of the rest of the staff.
 */
final readonly class GetSystemOverviewUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private ServerStatusInterface $server,
        private ScheduleHeartbeatInterface $heartbeat,
        private ServerHealth $health,
    ) {
    }

    public function execute(int $now): ?SystemOverviewDTO
    {
        if (! $this->accessChecker->allows(CorePermissions::ADMIN_SETTINGS_MANAGE)) {
            return null;
        }

        $lastRun = $this->heartbeat->lastBeat();
        $disk = $this->server->disk();

        return new SystemOverviewDTO(
            schedulerLastRun: $lastRun,
            schedulerRuns: $this->health->schedulerRuns($lastRun, $now),
            cronLine: '* * * * * php ' . ROOT_PATH . 'system/bin/console schedule:run --no-interaction',
            cmsVersion: CMS_VERSION,
            phpVersion: PHP_VERSION,
            databaseVersion: $this->server->databaseVersion(),
            diskFree: $disk['free'] ?? null,
            diskTotal: $disk['total'] ?? null,
            diskLow: $this->health->diskLow($disk),
            cacheSize: $this->server->cacheSize(),
            logsSize: $this->server->logsSize(),
            debug: DEBUG,
        );
    }
}
