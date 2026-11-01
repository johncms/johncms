<?php

declare(strict_types=1);

namespace Tests\Unit\Updates;

use Johncms\Cache\CacheInterface;
use Johncms\Database\ConnectionInterface;
use Johncms\Database\Migrations\MigrationLocator;
use Johncms\Database\Migrations\MigrationRepositoryInterface;
use Johncms\Database\Migrations\Migrator;
use Johncms\Database\Migrations\PendingMigrations;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleRepositoryInterface;
use Johncms\Modules\ModuleStateStore;
use Johncms\Scheduler\ScheduleMutexInterface;
use Johncms\Updates\SystemUpdateState;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class SystemUpdateStateTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/johncms-update-' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
    }

    public function testMissingMarkerAndChangedVersionRequireUpdate(): void
    {
        $state = $this->state();
        self::assertTrue($state->isRequired());
        $state->complete();
        self::assertFalse($this->state()->isRequired());
        file_put_contents($this->path, json_encode(['cms' => 'old', 'modules' => []]));
        self::assertTrue($state->isRequired());
    }

    public function testFailedRunRemainsPendingUntilSuccessfulCompletion(): void
    {
        $state = $this->state();
        $state->complete();
        $state->begin();
        self::assertTrue($this->state()->isRequired());
        $state->complete();
        self::assertFalse($this->state()->isRequired());
    }

    public function testPendingMigrationRequiresUpdateDespiteCurrentVersion(): void
    {
        $this->state()->complete();
        self::assertTrue($this->state(2)->isRequired());
    }

    public function testScopedCompletionDoesNotAcknowledgeFailedGlobalUpdate(): void
    {
        $state = $this->state();
        $state->complete();
        $state->begin();
        $state->complete('test/module');
        self::assertTrue($state->isRequired());
    }

    private function state(int $pending = 0): SystemUpdateState
    {
        $migrator = new Migrator(
            new MigrationLocator([]), $this->createStub(MigrationRepositoryInterface::class),
            $this->createStub(SchemaInterface::class), $this->createStub(ConnectionInterface::class),
            $this->createStub(ScheduleMutexInterface::class)
        );
        $cache = $this->createStub(CacheInterface::class);
        $cache->method('remember')->willReturn($pending);
        $repo = $this->createStub(ModuleRepositoryInterface::class);
        $repo->method('all')->willReturn([]);
        return new SystemUpdateState(
            new PendingMigrations($migrator, $cache, new NullLogger()),
            new ModuleRegistry($repo, new ModuleStateStore($this->path . '.php')), $this->path
        );
    }
}
