<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\AdminTasks\AdminTaskRegistry;
use Johncms\AdminTasks\FileAdminTaskStorage;
use Johncms\Modules\Admin\Application\Exceptions\MaintenanceTaskNotFoundException;

final readonly class QueueMaintenanceTaskUseCase
{
    public function __construct(
        private AdminTaskRegistry $registry,
        private FileAdminTaskStorage $storage,
    ) {
    }

    /**
     * Queue a background maintenance task for the scheduler.
     *
     * @throws MaintenanceTaskNotFoundException
     */
    public function execute(string $commandName): void
    {
        $task = $this->registry->find($commandName);
        if ($task === null || ! $task->background) {
            throw new MaintenanceTaskNotFoundException(sprintf('Background task "%s" is not registered.', $commandName));
        }

        $this->storage->queue($task->commandName);
    }
}
