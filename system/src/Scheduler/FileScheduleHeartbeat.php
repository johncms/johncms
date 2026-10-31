<?php

declare(strict_types=1);

namespace Johncms\Scheduler;

/**
 * The moment of the last run in a file of its own. Outside the cache on purpose: clearing the cache
 * would otherwise make a working cron look broken until its next minute.
 */
final readonly class FileScheduleHeartbeat implements ScheduleHeartbeatInterface
{
    public function __construct(
        private string $path = DATA_PATH . 'schedule' . DS . 'last-run',
    ) {
    }

    public function beat(int $time): void
    {
        $directory = \dirname($this->path);
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            return;
        }

        file_put_contents($this->path, (string) $time, LOCK_EX);
    }

    public function lastBeat(): ?int
    {
        if (! is_file($this->path)) {
            return null;
        }

        $contents = trim((string) file_get_contents($this->path));

        return ctype_digit($contents) ? (int) $contents : null;
    }
}
