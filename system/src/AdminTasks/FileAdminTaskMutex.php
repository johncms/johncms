<?php

declare(strict_types=1);

namespace Johncms\AdminTasks;

use Johncms\Scheduler\ScheduleMutexInterface;

/**
 * Lock files live outside CACHE_PATH because the cache:clear task
 * would delete schedule locks while they are held.
 */
final class FileAdminTaskMutex implements ScheduleMutexInterface
{
    public function acquire(string $key): mixed
    {
        $directory = DATA_PATH . 'admin_tasks' . DS . 'locks';
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            return null;
        }

        $path = $directory . DS . hash('sha256', $key) . '.lock';
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
