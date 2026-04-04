<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduler;

use Johncms\Scheduler\AsScheduledTask;
use Johncms\Scheduler\ScheduledTaskRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;

final class ScheduledTaskRegistryTest extends TestCase
{
    public function testAllReturnsDefinitionsForScheduledCommands(): void
    {
        $registry = new ScheduledTaskRegistry(
            [
                new RegistryScheduledCommandA(),
                new RegistryPlainCommand(),
            ]
        );

        $definitions = $registry->all();

        self::assertCount(1, $definitions);
        self::assertSame('task:a', $definitions[0]->commandName);
        self::assertSame('* * * * *', $definitions[0]->expression);
        self::assertSame('UTC', $definitions[0]->timezone);
        self::assertTrue($definitions[0]->withoutOverlapping);
        self::assertSame(['--flag' => '1'], $definitions[0]->arguments);
    }

    public function testAllReturnsDefinitionsSortedByCommandName(): void
    {
        $registry = new ScheduledTaskRegistry(
            [
                new RegistryScheduledCommandB(),
                new RegistryScheduledCommandA(),
            ]
        );

        $definitions = $registry->all();

        self::assertSame('task:a', $definitions[0]->commandName);
        self::assertSame('task:b', $definitions[1]->commandName);
    }

    public function testAllSkipsScheduledCommandWithoutName(): void
    {
        $registry = new ScheduledTaskRegistry([new RegistryNamelessScheduledCommand()]);

        self::assertSame([], $registry->all());
    }

    public function testAllReturnsMultipleDefinitionsForSingleCommandWithMultipleAttributes(): void
    {
        $registry = new ScheduledTaskRegistry([new RegistryMultiScheduledCommand()]);

        $definitions = $registry->all();

        self::assertCount(2, $definitions);
        self::assertSame('task:multi', $definitions[0]->commandName);
        self::assertSame('task:multi', $definitions[1]->commandName);
        self::assertSame('0 * * * *', $definitions[0]->expression);
        self::assertSame('30 3 * * *', $definitions[1]->expression);
    }
}

#[AsScheduledTask(expression: '* * * * *', timezone: 'UTC', arguments: ['--flag' => '1'], withoutOverlapping: true)]
final class RegistryScheduledCommandA extends Command
{
    public function __construct()
    {
        parent::__construct('task:a');
    }
}

#[AsScheduledTask(expression: '0 * * * *')]
final class RegistryScheduledCommandB extends Command
{
    public function __construct()
    {
        parent::__construct('task:b');
    }
}

final class RegistryPlainCommand extends Command
{
    public function __construct()
    {
        parent::__construct('task:plain');
    }
}

#[AsScheduledTask(expression: '* * * * *')]
final class RegistryNamelessScheduledCommand extends Command
{
}

#[AsScheduledTask(expression: '0 * * * *')]
#[AsScheduledTask(expression: '30 3 * * *')]
final class RegistryMultiScheduledCommand extends Command
{
    public function __construct()
    {
        parent::__construct('task:multi');
    }
}
