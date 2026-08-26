<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use DateTimeImmutable;
use Johncms\AdminTasks\AdminTaskBusyException;
use Johncms\AdminTasks\AdminTaskRegistry;
use Johncms\AdminTasks\AdminTaskRunner;
use Johncms\AdminTasks\AdminTaskStatus;
use Johncms\AdminTasks\FileAdminTaskStorage;
use Johncms\Scheduler\AsScheduledTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'admin-tasks:run-queued',
    description: 'Run admin maintenance tasks queued for background execution',
)]
#[AsScheduledTask(expression: '* * * * *', withoutOverlapping: true)]
final class ProcessQueuedAdminTasksCommand extends Command
{
    public function __construct(
        private readonly AdminTaskRegistry $registry,
        private readonly FileAdminTaskStorage $storage,
        private readonly AdminTaskRunner $runner,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();
        if ($application === null) {
            $output->writeln('<error>Console application is not available.</error>');
            return self::FAILURE;
        }

        $this->storage->healStale(new DateTimeImmutable());

        $hasFailures = false;

        foreach ($this->storage->getQueued() as $queued) {
            $commandName = $queued->commandName;
            $task = $this->registry->find($commandName);
            if ($task === null) {
                $this->storage->storeResult($commandName, 1, 'Unknown command.');
                $output->writeln(sprintf('<error>Skipped %s (unknown command).</error>', $commandName));
                $hasFailures = true;
                continue;
            }

            try {
                $state = $this->runner->run($task, $application, $queued->arguments);
            } catch (AdminTaskBusyException) {
                // Leave the task queued; it will be retried on the next run.
                $output->writeln(sprintf('<comment>Skipped %s (already running).</comment>', $commandName));
                continue;
            }

            if ($state?->status === AdminTaskStatus::Failed) {
                $hasFailures = true;
                $output->writeln(sprintf('<error>%s failed.</error>', $commandName));
                continue;
            }

            $output->writeln(sprintf('<info>%s completed.</info>', $commandName));
        }

        return $hasFailures ? self::FAILURE : self::SUCCESS;
    }
}
