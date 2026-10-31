<?php

declare(strict_types=1);

namespace Johncms\Scheduler;

/**
 * When the scheduler last ran. Cron starts schedule:run every minute, so a moment long gone means
 * cron is not set up or has stopped — and with it everything that runs on a schedule: the mail
 * queue, the cleanups, the tasks the panel queues.
 */
interface ScheduleHeartbeatInterface
{
    public function beat(int $time): void;

    /**
     * Null when the scheduler has never run on this site.
     */
    public function lastBeat(): ?int;
}
