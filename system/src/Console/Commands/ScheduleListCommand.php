<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Cron\CronExpression;
use Johncms\Scheduler\ScheduledTaskDefinition;
use Johncms\Scheduler\ScheduledTaskRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'schedule:list',
    description: 'List registered scheduled tasks',
)]
final class ScheduleListCommand extends Command
{
    public function __construct(
        private ScheduledTaskRegistry $taskRegistry,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tasks = $this->taskRegistry->all();

        if ($tasks === []) {
            $io->writeln('<comment>No scheduled tasks were found.</comment>');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($tasks as $task) {
            $rows[] = [
                $task->commandName,
                $task->expression,
                $task->timezone ?? 'default',
                $this->nextRunAt($task),
                $task->withoutOverlapping ? 'yes' : 'no',
                $task->description ?? '',
            ];
        }

        $io->table(
            ['Command', 'Expression', 'Timezone', 'Next Run', 'No Overlap', 'Description'],
            $rows
        );

        return self::SUCCESS;
    }

    private function nextRunAt(ScheduledTaskDefinition $task): string
    {
        try {
            $expression = CronExpression::factory($task->expression);
            $nextRun = $expression->getNextRunDate(
                currentTime: 'now',
                nth: 0,
                allowCurrentDate: false,
                timeZone: $task->timezone
            );

            return $nextRun->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return 'invalid expression';
        }
    }
}
