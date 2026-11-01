<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Johncms\Cache\CacheInterface;
use Johncms\Console\Commands\CacheClearCommand;
use Johncms\Console\Commands\SystemUpdateCommand;
use Johncms\Database\ConnectionInterface;
use Johncms\Database\Migrations\MigrationLocator;
use Johncms\Database\Migrations\MigrationRepositoryInterface;
use Johncms\Database\Migrations\MigrationRunnerInterface;
use Johncms\Database\Migrations\Migrator;
use Johncms\Database\Migrations\PendingMigrations;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Modules\ModuleInstallerFactory;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleRepositoryInterface;
use Johncms\Modules\ModuleStateStore;
use Johncms\Scheduler\ScheduleMutexInterface;
use Johncms\Updates\DeclarationSynchronizerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

final class SystemUpdateCommandTest extends TestCase
{
    public function testUpdateSynchronizesBeforeValidationAndCanRepeat(): void
    {
        $sync = new class implements DeclarationSynchronizerInterface {
            public int $runs = 0;
            public function owner(): string { return 'system'; }
            public function synchronize(): void { ++$this->runs; }
            public function validate(): void {
                if ($this->runs === 0) { throw new RuntimeException('Not synchronized'); }
            }
        };
        $tester = $this->command($sync);
        self::assertSame(0, $tester->execute([]));
        $tester->execute([]);
        self::assertSame(2, $sync->runs);
    }

    public function testValidationFailureReportsOwnerAndReleasesLock(): void
    {
        $sync = new class implements DeclarationSynchronizerInterface {
            public function owner(): string { return 'system'; }
            public function synchronize(): void {}
            public function validate(): void { throw new RuntimeException('Missing field'); }
        };
        $tester = $this->command($sync);
        self::assertSame(1, $tester->execute([]));
        self::assertStringContainsString('validate system: Missing field', $tester->getDisplay());
    }

    public function testConcurrentUpdateDoesNotRunMigrations(): void
    {
        $tester = $this->command(locked: true);
        self::assertSame(1, $tester->execute([]));
        self::assertStringContainsString('lock', $tester->getDisplay());
    }

    public function testBundledModuleHookRunsEvenWhenVersionsAreEqual(): void
    {
        \Tests\Unit\Console\UpdateFixture\Install\Installer::$versions = [];
        $manifest = new \Johncms\Modules\Manifest\ModuleManifest(
            key: 'test/fixture', alias: 'fixture', path: '/fixture', name: 'Fixture',
            autoload: new \Johncms\Modules\Manifest\ModuleAutoload(psr4: ['Tests\\Unit\\Console\\UpdateFixture\\' => 'src/'])
        );
        $tester = $this->command(modules: ['test/fixture' => $manifest]);
        self::assertSame(0, $tester->execute([]));
        self::assertSame([[CMS_VERSION, CMS_VERSION]], \Tests\Unit\Console\UpdateFixture\Install\Installer::$versions);
    }

    /** @param array<string, \Johncms\Modules\Manifest\ModuleManifest> $modules */
    private function command(?DeclarationSynchronizerInterface $sync = null, bool $locked = false, array $modules = []): CommandTester
    {
        $mutex = $this->createMock(ScheduleMutexInterface::class);
        $mutex->method('acquire')->willReturn($locked ? null : true);
        $mutex->expects(self::exactly($locked ? 0 : ($sync !== null && property_exists($sync, 'runs') ? 2 : 1)))
            ->method('release')->with(true);
        $runner = $this->createMock(MigrationRunnerInterface::class);
        $runner->expects($locked ? self::never() : self::atLeastOnce())->method('run')->willReturn([]);
        $migrator = new Migrator(
            new MigrationLocator([]), $this->createMock(MigrationRepositoryInterface::class),
            $this->createMock(SchemaInterface::class), $this->createMock(ConnectionInterface::class), $mutex
        );
        $state = new ModuleStateStore(sys_get_temp_dir() . '/absent-update-state-' . uniqid() . '.php');
        $repo = $this->createMock(ModuleRepositoryInterface::class);
        $repo->method('all')->willReturn($modules);
        return new CommandTester(new SystemUpdateCommand(
            $runner, new PendingMigrations($migrator, $this->createMock(CacheInterface::class), new NullLogger()),
            new ModuleRegistry($repo, $state, array_keys($modules)), $state, new ModuleInstallerFactory(), $mutex,
            new CacheClearCommand(sys_get_temp_dir() . '/absent-update-cache-' . uniqid()), $sync === null ? [] : [$sync]
        ));
    }
}

namespace Tests\Unit\Console\UpdateFixture\Install;

final class Installer extends \Johncms\Modules\Installer
{
    /** @var list<array{string, string}> */
    public static array $versions = [];

    public function update(string $from, string $to): void
    {
        self::$versions[] = [$from, $to];
    }
}
