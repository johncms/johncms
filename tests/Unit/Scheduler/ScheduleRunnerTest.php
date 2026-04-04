<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduler;

use DateTimeImmutable;
use Johncms\Scheduler\AsScheduledTask;
use Johncms\Scheduler\ScheduleMutexInterface;
use Johncms\Scheduler\ScheduleRunner;
use Johncms\Scheduler\ScheduledTaskRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class ScheduleRunnerTest extends TestCase
{
    public function testRunDueTasksExecutesOnlyDueTasks(): void
    {
        $dueCommand = new RunnerDueCommand();
        $notDueCommand = new RunnerNotDueCommand();

        $application = new Application();
        $application->addCommand($dueCommand);
        $application->addCommand($notDueCommand);

        $registry = new ScheduledTaskRegistry([$dueCommand, $notDueCommand]);

        $mutex = $this->createMock(ScheduleMutexInterface::class);
        $mutex->expects(self::never())->method('acquire');
        $mutex->expects(self::never())->method('release');

        $logger = $this->createMock(LoggerInterface::class);
        $runner = new ScheduleRunner($registry, $mutex, $logger);

        $output = new BufferedOutput();
        $exitCode = $runner->runDueTasks(
            new DateTimeImmutable('2026-04-04 12:34:00'),
            $application,
            $output
        );

        self::assertSame(0, $exitCode);
        self::assertSame(1, $dueCommand->runs);
        self::assertSame(0, $notDueCommand->runs);
        self::assertStringContainsString('Due scheduled tasks executed: 1.', $output->fetch());
    }

    public function testRunDueTasksSkipsWhenOverlapLockIsNotAcquired(): void
    {
        $command = new RunnerLockedCommand();

        $application = new Application();
        $application->addCommand($command);
        $registry = new ScheduledTaskRegistry([$command]);

        $mutex = $this->createMock(ScheduleMutexInterface::class);
        $mutex->expects(self::once())->method('acquire')->willReturn(null);

        $logger = $this->createMock(LoggerInterface::class);
        $runner = new ScheduleRunner($registry, $mutex, $logger);

        $output = new BufferedOutput();
        $exitCode = $runner->runDueTasks(
            new DateTimeImmutable('2026-04-04 12:34:00'),
            $application,
            $output
        );

        self::assertSame(0, $exitCode);
        self::assertSame(0, $command->runs);
        self::assertStringContainsString('Skipped task:locked (already running).', $output->fetch());
    }

    public function testRunDueTasksReturnsFailureWhenTaskCommandFails(): void
    {
        $command = new RunnerFailingLockedCommand();

        $application = new Application();
        $application->addCommand($command);
        $registry = new ScheduledTaskRegistry([$command]);

        $lock = fopen('php://temp', 'r+');
        self::assertNotFalse($lock);

        $mutex = $this->createMock(ScheduleMutexInterface::class);
        $mutex->expects(self::once())->method('acquire')->willReturn($lock);
        $mutex->expects(self::once())->method('release')->with($lock);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('Scheduled task failed.', ['command' => 'task:fails', 'exit_code' => 1]);

        $runner = new ScheduleRunner($registry, $mutex, $logger);

        $output = new BufferedOutput();
        $exitCode = $runner->runDueTasks(
            new DateTimeImmutable('2026-04-04 12:34:00'),
            $application,
            $output
        );

        self::assertSame(1, $exitCode);
        self::assertSame(1, $command->runs);
    }

    public function testRunDueTasksLogsInvalidCronExpression(): void
    {
        $command = new RunnerInvalidExpressionCommand();

        $application = new Application();
        $application->addCommand($command);
        $registry = new ScheduledTaskRegistry([$command]);

        $mutex = $this->createMock(ScheduleMutexInterface::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Scheduled task has invalid cron expression.',
                self::callback(static fn (array $context): bool => $context['command'] === 'task:invalid')
            );

        $runner = new ScheduleRunner($registry, $mutex, $logger);

        $output = new BufferedOutput();
        $exitCode = $runner->runDueTasks(
            new DateTimeImmutable('2026-04-04 12:34:00'),
            $application,
            $output
        );

        self::assertSame(0, $exitCode);
        self::assertSame(0, $command->runs);
    }

    public function testRunDueTasksPassesConfiguredArgumentsToCommand(): void
    {
        $command = new RunnerArgsCommand();

        $application = new Application();
        $application->addCommand($command);
        $registry = new ScheduledTaskRegistry([$command]);

        $mutex = $this->createMock(ScheduleMutexInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $runner = new ScheduleRunner($registry, $mutex, $logger);

        $output = new BufferedOutput();
        $exitCode = $runner->runDueTasks(
            new DateTimeImmutable('2026-04-04 12:34:00'),
            $application,
            $output
        );

        self::assertSame(0, $exitCode);
        self::assertSame(1, $command->runs);
        self::assertSame('7', $command->receivedLimit);
    }

    public function testRunDueTasksReleasesLockAndLogsWhenCommandThrowsException(): void
    {
        $command = new RunnerThrowingLockedCommand();

        $application = new Application();
        $application->addCommand($command);
        $registry = new ScheduledTaskRegistry([$command]);

        $lock = fopen('php://temp', 'r+');
        self::assertNotFalse($lock);

        $mutex = $this->createMock(ScheduleMutexInterface::class);
        $mutex->expects(self::once())->method('acquire')->willReturn($lock);
        $mutex->expects(self::once())->method('release')->with($lock);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Scheduled task failed with exception.',
                self::callback(static fn (array $context): bool => $context['command'] === 'task:throws')
            );

        $runner = new ScheduleRunner($registry, $mutex, $logger);

        $output = new BufferedOutput();
        $exitCode = $runner->runDueTasks(
            new DateTimeImmutable('2026-04-04 12:34:00'),
            $application,
            $output
        );

        self::assertSame(1, $exitCode);
        self::assertSame(1, $command->runs);
        self::assertStringContainsString('task:throws failed: boom', $output->fetch());
    }

    public function testRunDueTasksRespectsTaskTimezoneForDueCalculation(): void
    {
        $moscowDue = new RunnerTimezoneMoscowDueCommand();
        $utcNotDue = new RunnerTimezoneUtcNotDueCommand();

        $application = new Application();
        $application->addCommand($moscowDue);
        $application->addCommand($utcNotDue);
        $registry = new ScheduledTaskRegistry([$moscowDue, $utcNotDue]);

        $mutex = $this->createMock(ScheduleMutexInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $runner = new ScheduleRunner($registry, $mutex, $logger);

        $output = new BufferedOutput();
        $exitCode = $runner->runDueTasks(
            new DateTimeImmutable('2026-04-04 10:30:00'),
            $application,
            $output
        );

        self::assertSame(0, $exitCode);
        self::assertSame(1, $moscowDue->runs);
        self::assertSame(0, $utcNotDue->runs);
    }
}

abstract class RunnerSpyCommand extends Command
{
    public int $runs = 0;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->runs++;
        return $this->resultCode();
    }

    protected function resultCode(): int
    {
        return Command::SUCCESS;
    }
}

#[AsScheduledTask(expression: '* * * * *')]
final class RunnerDueCommand extends RunnerSpyCommand
{
    public function __construct()
    {
        parent::__construct('task:due');
    }
}

#[AsScheduledTask(expression: '0 0 1 1 *')]
final class RunnerNotDueCommand extends RunnerSpyCommand
{
    public function __construct()
    {
        parent::__construct('task:not-due');
    }
}

#[AsScheduledTask(expression: '* * * * *', withoutOverlapping: true)]
final class RunnerLockedCommand extends RunnerSpyCommand
{
    public function __construct()
    {
        parent::__construct('task:locked');
    }
}

#[AsScheduledTask(expression: '* * * * *', withoutOverlapping: true)]
final class RunnerFailingLockedCommand extends RunnerSpyCommand
{
    public function __construct()
    {
        parent::__construct('task:fails');
    }

    protected function resultCode(): int
    {
        return Command::FAILURE;
    }
}

#[AsScheduledTask(expression: 'invalid-expression')]
final class RunnerInvalidExpressionCommand extends RunnerSpyCommand
{
    public function __construct()
    {
        parent::__construct('task:invalid');
    }
}

#[AsScheduledTask(expression: '* * * * *', arguments: ['--limit' => '7'])]
final class RunnerArgsCommand extends RunnerSpyCommand
{
    public ?string $receivedLimit = null;

    public function __construct()
    {
        parent::__construct('task:args');
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->runs++;
        $this->receivedLimit = $input->getParameterOption('--limit');
        return self::SUCCESS;
    }
}

#[AsScheduledTask(expression: '* * * * *', withoutOverlapping: true)]
final class RunnerThrowingLockedCommand extends RunnerSpyCommand
{
    public function __construct()
    {
        parent::__construct('task:throws');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->runs++;
        throw new \RuntimeException('boom');
    }
}

#[AsScheduledTask(expression: '30 13 * * *', timezone: 'Europe/Moscow')]
final class RunnerTimezoneMoscowDueCommand extends RunnerSpyCommand
{
    public function __construct()
    {
        parent::__construct('task:tz-moscow-due');
    }
}

#[AsScheduledTask(expression: '30 13 * * *', timezone: 'UTC')]
final class RunnerTimezoneUtcNotDueCommand extends RunnerSpyCommand
{
    public function __construct()
    {
        parent::__construct('task:tz-utc-not-due');
    }
}
