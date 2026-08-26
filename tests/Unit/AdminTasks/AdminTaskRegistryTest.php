<?php

declare(strict_types=1);

namespace Tests\Unit\AdminTasks;

use Johncms\AdminTasks\AdminTaskRegistry;
use Johncms\AdminTasks\AsAdminTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use PHPUnit\Framework\TestCase;

/**
 * Which commands the maintenance screen offers, and which are only queued from elsewhere.
 */
final class AdminTaskRegistryTest extends TestCase
{
    public function testACommandMarkedAsATaskIsCollected(): void
    {
        $definitions = (new AdminTaskRegistry([new ListedTaskCommand()]))->all();

        self::assertCount(1, $definitions);
        self::assertSame('test:listed', $definitions[0]->commandName);
        self::assertTrue($definitions[0]->listed);
        self::assertFalse($definitions[0]->background);
    }

    /**
     * A command that needs an argument has nothing to offer a screen of buttons: it is queued from
     * wherever that argument comes from — the modules section, for instance.
     */
    public function testATaskThatNeedsArgumentsIsCollectedButNotListed(): void
    {
        $definitions = (new AdminTaskRegistry([new UnlistedTaskCommand()]))->all();

        self::assertCount(1, $definitions);
        self::assertFalse($definitions[0]->listed);
        self::assertTrue($definitions[0]->background);
        self::assertNotNull((new AdminTaskRegistry([new UnlistedTaskCommand()]))->find('test:unlisted'));
    }

    public function testACommandWithoutTheAttributeIsNotATask(): void
    {
        self::assertSame([], (new AdminTaskRegistry([new PlainCommand()]))->all());
    }
}

#[AsCommand(name: 'test:listed', description: 'Listed')]
#[AsAdminTask(title: 'Listed task')]
final class ListedTaskCommand extends Command
{
}

#[AsCommand(name: 'test:unlisted', description: 'Unlisted')]
#[AsAdminTask(title: 'Unlisted task', background: true, listed: false)]
final class UnlistedTaskCommand extends Command
{
}

#[AsCommand(name: 'test:plain', description: 'Plain')]
final class PlainCommand extends Command
{
}
