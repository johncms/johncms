<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\SystemChecks;

use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\SystemCheckInterface;
use Johncms\Modules\Admin\Domain\Services\ServerHealth;
use Johncms\Scheduler\ScheduleHeartbeatInterface;

final readonly class SchedulerCheck implements SystemCheckInterface
{
    public function __construct(private ScheduleHeartbeatInterface $heartbeat, private ServerHealth $health)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO('scheduler.heartbeat', d__('admin', 'Task scheduler (cron)'), d__('system', 'Background tasks'), 'clock', order: 60);
    }

    public function run(): array
    {
        $lastRun = $this->heartbeat->lastBeat();
        $now = time();
        $running = $this->health->schedulerRuns($lastRun, $now);

        return [new CheckResultDTO(
            'scheduler.heartbeat',
            self::definition()->name,
            $lastRun === null ? d__('admin', 'Never run') : gmdate('Y-m-d H:i:s', $lastRun) . ' UTC',
            $running ? CheckStatus::Passed : CheckStatus::Failed,
            CheckSeverity::Critical,
            d__('admin', 'Cron should run php system/bin/console schedule:run every minute. A heartbeat older than %d minutes means background tasks may have stopped.', (int) (ServerHealth::SCHEDULER_STALE_AFTER / 60)),
        )];
    }
}
