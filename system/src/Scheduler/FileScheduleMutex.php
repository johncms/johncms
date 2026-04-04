<?php

declare(strict_types=1);

namespace Johncms\Scheduler;

final class FileScheduleMutex implements ScheduleMutexInterface
{
    public function acquire(string $key): mixed
    {
        $directory = CACHE_PATH . 'schedule';
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            return null;
        }

        $path = $directory . '/' . hash('sha256', $key) . '.lock';
        $handle = fopen($path, 'c+');

        if ($handle === false) {
            return null;
        }

        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return null;
        }

        return $handle;
    }

    public function release(mixed $lock): void
    {
        if (! is_resource($lock)) {
            return;
        }

        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
