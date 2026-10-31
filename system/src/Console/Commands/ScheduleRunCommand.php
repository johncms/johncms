<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use DateTimeImmutable;
use Johncms\Scheduler\ScheduleHeartbeatInterface;
use Johncms\Scheduler\ScheduleRunner;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'schedule:run',
    description: 'Run scheduled tasks that are due',
)]
final class ScheduleRunCommand extends Command
{
    public function __construct(
        private ScheduleRunner $scheduleRunner,
        private ScheduleHeartbeatInterface $heartbeat,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $application = $this->getApplication();
        if (! $application instanceof Application) {
            $io->error('Console application is unavailable.');
            return self::FAILURE;
        }

        $now = new DateTimeImmutable('now');
        // Before the tasks: a task that hangs must not make cron look stopped.
        $this->heartbeat->beat($now->getTimestamp());

        return $this->scheduleRunner->runDueTasks(
            now: $now,
            application: $application,
            output: $io,
        );
    }
}
