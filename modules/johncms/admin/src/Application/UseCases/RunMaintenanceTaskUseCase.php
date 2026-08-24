<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\AdminTasks\AdminTaskRegistry;
use Johncms\AdminTasks\AdminTaskRunner;
use Johncms\AdminTasks\AdminTaskState;
use Johncms\Modules\Admin\Application\Exceptions\MaintenanceTaskNotFoundException;
use Symfony\Component\Console\Application;

final readonly class RunMaintenanceTaskUseCase
{
    public function __construct(
        private AdminTaskRegistry $registry,
        private AdminTaskRunner $runner,
        private Application $application,
    ) {
    }

    /**
     * Run a foreground maintenance task synchronously.
     *
     * @throws MaintenanceTaskNotFoundException
     * @throws \Johncms\AdminTasks\AdminTaskBusyException
     */
    public function execute(string $commandName): ?AdminTaskState
    {
        $task = $this->registry->find($commandName);
        if ($task === null || $task->background) {
            throw new MaintenanceTaskNotFoundException(sprintf('Foreground task "%s" is not registered.', $commandName));
        }

        return $this->runner->run($task, $this->application);
    }
}
