<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use DateTimeImmutable;
use Johncms\Scheduler\ScheduleRunner;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'schedule:run',
    description: 'Run scheduled tasks that are due',
)]
final class ScheduleRunCommand extends Command
{
    public function __construct(
        private ScheduleRunner $scheduleRunner,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();
        if (! $application instanceof Application) {
            $output->writeln('<error>Console application is unavailable.</error>');
            return self::FAILURE;
        }

        return $this->scheduleRunner->runDueTasks(
            now: new DateTimeImmutable('now'),
            application: $application,
            output: $output,
        );
    }
}
