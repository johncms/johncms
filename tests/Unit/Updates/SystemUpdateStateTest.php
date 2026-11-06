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
use Johncms\Modules\Manifest\ModuleManifest;
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

    public function testAModuleIsNotHeldBackByTheUpdateOfAnother(): void
    {
        $modules = ['test/stable' => $this->manifest('test/stable', 'stable'), 'test/failing' => $this->manifest('test/failing', 'failing')];
        $state = $this->state(modules: $modules);
        self::assertTrue($state->isRequiredFor('test/stable'));
        $state->complete();
        self::assertFalse($state->isRequiredFor('test/stable'));
        $state->begin('test/failing');
        self::assertTrue($state->isRequired());
        self::assertTrue($state->isRequiredFor('test/failing'));
        self::assertFalse($state->isRequiredFor('test/stable'));
        self::assertTrue($state->isRequiredFor('test/stable', 'test/failing'));
        self::assertTrue($state->isRequiredFor('test/missing'));
    }

    public function testMigrationsOfTheModuleItselfHoldItBack(): void
    {
        $modules = ['test/own' => $this->manifest('test/own', 'test'), 'test/other' => $this->manifest('test/other', 'other')];
        $this->state(modules: $modules)->complete();
        self::assertTrue($this->state(1, $modules)->isRequiredFor('test/own'));
        self::assertFalse($this->state(1, $modules)->isRequiredFor('test/other'));
    }

    public function testScopedCompletionDoesNotAcknowledgeFailedGlobalUpdate(): void
    {
        $state = $this->state();
        $state->complete();
        $state->begin();
        $state->complete('test/module');
        self::assertTrue($state->isRequired());
    }

    /** @param array<string, ModuleManifest> $modules */
    private function state(int $pending = 0, array $modules = []): SystemUpdateState
    {
        $migrator = new Migrator(
            new MigrationLocator([]), $this->createStub(MigrationRepositoryInterface::class),
            $this->createStub(SchemaInterface::class), $this->createStub(ConnectionInterface::class),
            $this->createStub(ScheduleMutexInterface::class)
        );
        $cache = $this->createStub(CacheInterface::class);
        $cache->method('remember')->willReturn($pending > 0 ? ['test' => $pending] : []);
        $repo = $this->createStub(ModuleRepositoryInterface::class);
        $repo->method('all')->willReturn($modules);
        return new SystemUpdateState(
            new PendingMigrations($migrator, $cache, new NullLogger()),
            new ModuleRegistry($repo, new ModuleStateStore($this->path . '.php'), array_keys($modules)), $this->path
        );
    }

    private function manifest(string $key, string $alias): ModuleManifest
    {
        return new ModuleManifest(key: $key, alias: $alias, path: sys_get_temp_dir(), name: $key);
    }
}
