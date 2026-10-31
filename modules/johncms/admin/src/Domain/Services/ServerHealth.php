<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Services;

/**
 * Where the line between fine and a problem runs, for the scheduler and the disk: the system block
 * and the lines that need attention draw it in the same place.
 */
final readonly class ServerHealth
{
    /** Cron starts the scheduler every minute; a few missed minutes are a busy host, ten are not. */
    public const int SCHEDULER_STALE_AFTER = 600;

    /** Less than a tenth of the disk left: uploads, the logs and the cache start failing soon after. */
    public const float DISK_LOW_SHARE = 0.1;

    public function schedulerRuns(?int $lastRun, int $now): bool
    {
        return $lastRun !== null && $now - $lastRun <= self::SCHEDULER_STALE_AFTER;
    }

    /**
     * @param array{free: int, total: int}|null $disk
     */
    public function diskLow(?array $disk): bool
    {
        return $disk !== null && $disk['free'] / $disk['total'] < self::DISK_LOW_SHARE;
    }
}
