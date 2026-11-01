<?php

declare(strict_types=1);

namespace Johncms\Updates;

use RuntimeException;

/** A fresh PHP boot loads the services of newly installed or enabled modules. */
final class ConsoleModuleUpdateFinisher implements ModuleUpdateFinisherInterface
{
    public function finish(string $module): void
    {
        $output = tmpfile();
        if ($output === false) {
            throw new RuntimeException('Cannot create the module update log.');
        }
        try {
            $process = proc_open(
                [PHP_BINDIR . '/php', ROOT_PATH . 'system/bin/console', 'system:update', '--module=' . $module, '--no-interaction'],
                [0 => ['pipe', 'r'], 1 => $output, 2 => $output],
                $pipes,
                ROOT_PATH
            );
            if (! is_resource($process)) {
                throw new RuntimeException('Cannot start the module update process.');
            }
            fclose($pipes[0]);
            $code = proc_close($process);
            rewind($output);
            $log = stream_get_contents($output);
            if ($code !== 0) {
                throw new RuntimeException('Module update failed. Run system:update to retry. ' . ($log === false ? '' : $log));
            }
        } finally {
            fclose($output);
        }
    }
}
