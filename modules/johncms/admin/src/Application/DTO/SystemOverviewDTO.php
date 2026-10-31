<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * The system block of the dashboard: whether the scheduler runs, the versions, the disk, the cache
 * and the logs.
 */
final readonly class SystemOverviewDTO
{
    /**
     * @param int|null $schedulerLastRun Null when the scheduler has never run on this site.
     * @param string   $cronLine         The line of crontab that runs the scheduler of this site.
     * @param int|null $diskFree         Null, as is the total, where the host does not tell.
     */
    public function __construct(
        public ?int $schedulerLastRun,
        public bool $schedulerRuns,
        public string $cronLine,
        public string $cmsVersion,
        public string $phpVersion,
        public string $databaseVersion,
        public ?int $diskFree,
        public ?int $diskTotal,
        public bool $diskLow,
        public int $cacheSize,
        public int $logsSize,
        public bool $debug,
    ) {
    }
}
