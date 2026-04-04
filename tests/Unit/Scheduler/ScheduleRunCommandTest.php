<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduler;

use Johncms\Console\Commands\ScheduleRunCommand;
use Johncms\Scheduler\AsScheduledTask;
use Johncms\Scheduler\ScheduleMutexInterface;
use Johncms\Scheduler\ScheduleRunner;
use Johncms\Scheduler\ScheduledTaskRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class ScheduleRunCommandTest extends TestCase
{
    public function testExecuteReturnsRunnerExitCode(): void
    {
        $failingCommand = new TestScheduleRunFailingCommand();
        $registry = new ScheduledTaskRegistry([$failingCommand]);
        $runner = new ScheduleRunner($registry, new TestNoopScheduleMutex(), $this->createMock(LoggerInterface::class));

        $command = new ScheduleRunCommand($runner);
        $application = new Application();
        $application->add($failingCommand);
        $application->add($command);

        $tester = new CommandTester($application->find('schedule:run'));
        $exitCode = $tester->execute([]);

        self::assertSame(1, $exitCode);
    }

    public function testExecuteFailsWhenApplicationIsUnavailable(): void
    {
        $registry = new ScheduledTaskRegistry([]);
        $runner = new ScheduleRunner($registry, new TestNoopScheduleMutex(), $this->createMock(LoggerInterface::class));

        $command = new ScheduleRunCommand($runner);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Console application is unavailable.', $tester->getDisplay());
    }
}

final class TestNoopScheduleMutex implements ScheduleMutexInterface
{
    public function acquire(string $key): mixed
    {
        return fopen('php://temp', 'r+');
    }

    public function release(mixed $lock): void
    {
    }
}

#[AsScheduledTask(expression: '* * * * *')]
final class TestScheduleRunFailingCommand extends Command
{
    public function __construct()
    {
        parent::__construct('task:run-fail');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::FAILURE;
    }
}
