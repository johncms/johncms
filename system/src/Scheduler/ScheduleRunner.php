<?php

declare(strict_types=1);

namespace Johncms\Scheduler;

use Cron\CronExpression;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final readonly class ScheduleRunner
{
    public function __construct(
        private ScheduledTaskRegistry $taskRegistry,
        private ScheduleMutexInterface $mutex,
        private LoggerInterface $logger,
    ) {
    }

    public function runDueTasks(
        DateTimeImmutable $now,
        Application $application,
        OutputInterface $output,
    ): int {
        $hasFailures = false;
        $executedTasks = 0;

        foreach ($this->taskRegistry->all() as $task) {
            if (! $this->isDue($task, $now)) {
                continue;
            }

            $lock = null;
            if ($task->withoutOverlapping) {
                $lock = $this->mutex->acquire($task->lockKey());
                if ($lock === null) {
                    $output->writeln(sprintf('<comment>Skipped %s (already running).</comment>', $task->commandName));
                    continue;
                }
            }

            try {
                $executedTasks++;
                $exitCode = $this->runTaskCommand($application, $task, $output);
                if ($exitCode !== 0) {
                    $hasFailures = true;
                }
            } finally {
                if ($lock !== null) {
                    $this->mutex->release($lock);
                }
            }
        }

        $output->writeln(sprintf('<info>Due scheduled tasks executed: %d.</info>', $executedTasks));

        return $hasFailures ? 1 : 0;
    }

    private function isDue(ScheduledTaskDefinition $task, DateTimeImmutable $now): bool
    {
        try {
            $cronExpression = new CronExpression($task->expression);
            return $cronExpression->isDue($now, $task->timezone);
        } catch (Throwable $exception) {
            $this->logger->error(
                'Scheduled task has invalid cron expression.',
                [
                    'command' => $task->commandName,
                    'expression' => $task->expression,
                    'exception' => $exception,
                ]
            );

            return false;
        }
    }

    private function runTaskCommand(
        Application $application,
        ScheduledTaskDefinition $task,
        OutputInterface $output,
    ): int {
        try {
            $command = $application->find($task->commandName);
            $input = new ArrayInput(['command' => $task->commandName, ...$task->arguments]);
            $input->setInteractive(false);

            $output->writeln(sprintf('<comment>Running %s...</comment>', $task->commandName));
            $exitCode = $command->run($input, $output);

            if ($exitCode !== 0) {
                $this->logger->error(
                    'Scheduled task failed.',
                    ['command' => $task->commandName, 'exit_code' => $exitCode]
                );
            }

            return $exitCode;
        } catch (Throwable $exception) {
            $this->logger->error(
                'Scheduled task failed with exception.',
                ['command' => $task->commandName, 'exception' => $exception]
            );
            $output->writeln(sprintf('<error>%s failed: %s</error>', $task->commandName, $exception->getMessage()));
            return 1;
        }
    }
}
