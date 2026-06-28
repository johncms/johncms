<?php

declare(strict_types=1);

namespace Johncms\Console;

/**
 * Tracks one-time console tasks (legacy data converters) to guard against
 * accidental re-runs. State is stored in a gitignored local config file, so
 * no database table or migration is required.
 */
final class OneTimeTaskTracker
{
    private const CONFIG_KEY = 'one_time_tasks';

    public function isCompleted(string $task): bool
    {
        return (bool) config(self::CONFIG_KEY . '.' . $task);
    }

    public function markCompleted(string $task): void
    {
        $tasks = config(self::CONFIG_KEY) ?? [];
        $tasks[$task] = date(DATE_ATOM);

        $content = "<?php\n\n" . 'return ' . var_export([self::CONFIG_KEY => $tasks], true) . ";\n";

        if (file_put_contents(CONFIG_PATH . 'autoload/one_time_tasks.local.php', $content) === false) {
            throw new \RuntimeException('Can not write file `one_time_tasks.local.php`');
        }
    }
}
