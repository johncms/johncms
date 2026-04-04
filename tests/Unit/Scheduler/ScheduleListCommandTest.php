<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduler;

use Johncms\Console\Commands\ScheduleListCommand;
use Johncms\Scheduler\ScheduledTaskDefinition;
use Johncms\Scheduler\ScheduledTaskRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ScheduleListCommandTest extends TestCase
{
    public function testExecuteShowsMessageWhenNoTasksFound(): void
    {
        $tester = new CommandTester(
            new ScheduleListCommand(new ScheduledTaskRegistry([]))
        );

        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('No scheduled tasks were found.', $tester->getDisplay());
    }

    public function testExecuteRendersTaskTable(): void
    {
        $registry = new ScheduledTaskRegistry(
            [
                new TestScheduledListMailCommand(),
                new TestScheduledListCleanupCommand(),
            ]
        );

        $tester = new CommandTester(new ScheduleListCommand($registry));
        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('mail:send-pending', $display);
        self::assertStringContainsString('forum:cleanup-orphan-files', $display);
        self::assertStringContainsString('yes', $display);
        self::assertStringContainsString('no', $display);
    }

    public function testExecuteMarksInvalidCronExpressionInTable(): void
    {
        $registry = new ScheduledTaskRegistry([new TestScheduledListInvalidCommand()]);

        $tester = new CommandTester(new ScheduleListCommand($registry));
        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('invalid expression', $tester->getDisplay());
    }
}

#[\Johncms\Scheduler\AsScheduledTask(expression: '* * * * *')]
final class TestScheduledListMailCommand extends \Symfony\Component\Console\Command\Command
{
    public function __construct()
    {
        parent::__construct('mail:send-pending');
    }
}

#[\Johncms\Scheduler\AsScheduledTask(expression: '0 * * * *', timezone: 'UTC', withoutOverlapping: true)]
final class TestScheduledListCleanupCommand extends \Symfony\Component\Console\Command\Command
{
    public function __construct()
    {
        parent::__construct('forum:cleanup-orphan-files');
    }
}

#[\Johncms\Scheduler\AsScheduledTask(expression: 'invalid-expression')]
final class TestScheduledListInvalidCommand extends \Symfony\Component\Console\Command\Command
{
    public function __construct()
    {
        parent::__construct('task:broken');
    }
}
