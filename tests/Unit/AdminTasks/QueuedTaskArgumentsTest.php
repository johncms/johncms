<?php

declare(strict_types=1);

namespace Tests\Unit\AdminTasks;

use Johncms\AdminTasks\AdminTaskStatus;
use Johncms\AdminTasks\FileAdminTaskStorage;
use PHPUnit\Framework\TestCase;

/**
 * A queued task is run later, by the scheduler, in another process — so whatever it needs to know
 * has to travel with it. "Install a module" is not a task until it says which module.
 */
final class QueuedTaskArgumentsTest extends TestCase
{
    private string $path;

    private FileAdminTaskStorage $storage;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . DS . 'johncms-tasks-' . uniqid();
        $this->storage = new FileAdminTaskStorage($this->path);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->path));
    }

    public function testTheArgumentsOfAQueuedTaskSurviveUntilItRuns(): void
    {
        $this->storage->queue('module:install', ['module' => 'vasya/blog', '--demo' => true]);

        $queued = (new FileAdminTaskStorage($this->path))->getQueued();

        self::assertCount(1, $queued);
        self::assertSame('module:install', $queued[0]->commandName);
        self::assertSame(['module' => 'vasya/blog', '--demo' => true], $queued[0]->arguments);
        self::assertSame(AdminTaskStatus::Queued, $queued[0]->status);
    }

    public function testATaskWithoutArgumentsCarriesNone(): void
    {
        $this->storage->queue('cache:clear');

        self::assertSame([], $this->storage->getQueued()[0]->arguments);
    }

    /**
     * Two different modules are two different tasks, and the second must not quietly become the
     * first — the file is keyed by command name, so this is worth pinning.
     */
    public function testQueueingTheSameCommandAgainReplacesItsArguments(): void
    {
        $this->storage->queue('module:install', ['module' => 'vasya/blog']);
        $this->storage->queue('module:install', ['module' => 'petya/shop']);

        $queued = $this->storage->getQueued();

        self::assertCount(1, $queued);
        self::assertSame(['module' => 'petya/shop'], $queued[0]->arguments);
    }

    public function testARunTaskIsNoLongerQueued(): void
    {
        $this->storage->queue('module:install', ['module' => 'vasya/blog']);
        $this->storage->storeResult('module:install', 0, 'done');

        self::assertSame([], $this->storage->getQueued());
        self::assertSame(AdminTaskStatus::Done, $this->storage->getState('module:install')?->status);
    }
}
