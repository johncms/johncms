<?php

declare(strict_types=1);

namespace Johncms\AdminTasks;

use Johncms\Scheduler\ScheduleMutexInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

final readonly class AdminTaskRunner
{
    public function __construct(
        private FileAdminTaskStorage $storage,
        private ScheduleMutexInterface $mutex,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * The console application is passed as an argument (not injected) to avoid
     * a circular dependency: the application is built from all commands, and
     * ProcessQueuedAdminTasksCommand depends on this runner.
     *
     * @throws AdminTaskBusyException
     */
    public function run(AdminTaskDefinition $task, Application $application): ?AdminTaskState
    {
        $lock = $this->mutex->acquire($task->lockKey());
        if ($lock === null) {
            throw new AdminTaskBusyException(sprintf('Task "%s" is already running.', $task->commandName));
        }

        try {
            $this->storage->markRunning($task->commandName);

            $output = new BufferedOutput();

            try {
                $command = $application->find($task->commandName);
                $input = new ArrayInput(['command' => $task->commandName]);
                $input->setInteractive(false);

                $exitCode = $command->run($input, $output);
            } catch (Throwable $exception) {
                $this->logger->error(
                    'Admin task failed with exception.',
                    ['command' => $task->commandName, 'exception' => $exception]
                );
                $this->storage->storeResult(
                    $task->commandName,
                    1,
                    rtrim($output->fetch() . PHP_EOL . $exception->getMessage())
                );

                return $this->storage->getState($task->commandName);
            }

            if ($exitCode !== 0) {
                $this->logger->error(
                    'Admin task failed.',
                    ['command' => $task->commandName, 'exit_code' => $exitCode]
                );
            }

            $this->storage->storeResult($task->commandName, $exitCode, $output->fetch());

            return $this->storage->getState($task->commandName);
        } finally {
            $this->mutex->release($lock);
        }
    }
}
