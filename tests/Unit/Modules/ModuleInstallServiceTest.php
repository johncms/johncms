<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use Illuminate\Support\Collection;
use Johncms\Auth\Authorization\DefaultPermissions;
use Johncms\Auth\Authorization\DefaultPermissionsApplier;
use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Auth\Authorization\RolePermissionPurger;
use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Database\Migrations\MigrationFile;
use Johncms\Database\Migrations\MigrationRunnerInterface;
use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\Modules\Manifest\ModuleRequirements;
use Johncms\Modules\ModuleAssetPublisher;
use Johncms\Modules\ModuleCacheInvalidator;
use Johncms\Modules\ModuleInstallService;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleRepositoryInterface;
use Johncms\Modules\ModuleStateRecord;
use Johncms\Modules\ModuleStateStore;
use PHPUnit\Framework\TestCase;

/**
 * The operations themselves. What they refuse is as much the point as what they do: every refusal
 * here is a site that would otherwise be left half-working, with no obvious way back.
 */
final class ModuleInstallServiceTest extends TestCase
{
    private string $root;

    private string $stateFile;

    /** @var list<string> */
    private array $irreversibleMigrations = [];

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DS . 'johncms-lifecycle-' . uniqid() . DS;
        mkdir($this->root, 0o777, true);
        $this->stateFile = $this->root . 'state.php';
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    public function testInstallingRecordsTheModuleAndRunsItsMigrations(): void
    {
        $service = $this->service(['vasya/blog' => []]);

        $result = $service->install('vasya/blog');

        self::assertTrue($result->isSuccessful());
        self::assertSame(['run', 'blog'], $this->migratorCalls[0] ?? []);

        $record = (new ModuleStateStore($this->stateFile))->find('vasya/blog');
        self::assertNotNull($record);
        self::assertTrue($record->installed);
        self::assertTrue($record->enabled);
        self::assertSame('blog', $record->alias);
    }

    public function testAModuleThatIsNotOnDiskCannotBeInstalled(): void
    {
        $result = $this->service([])->install('vasya/blog');

        self::assertFalse($result->isSuccessful());
        self::assertStringContainsString('no module "vasya/blog" in the modules directory', (string) $result->error());
    }

    public function testAModuleIsNotInstalledTwice(): void
    {
        $service = $this->service(['vasya/blog' => []], state: ['vasya/blog' => new ModuleStateRecord('vasya/blog', 'blog')]);

        $result = $service->install('vasya/blog');

        self::assertFalse($result->isSuccessful());
        self::assertStringContainsString('already installed', (string) $result->error());
    }

    /**
     * The alias is a Twig namespace, a translation domain and the name of a migration source. Two
     * modules holding one would each get half of the other's templates and messages.
     */
    public function testAModuleWhoseAliasIsTakenCannotBeInstalled(): void
    {
        $service = $this->service(
            ['vasya/blog' => [], 'petya/blog' => []],
            state: ['vasya/blog' => new ModuleStateRecord('vasya/blog', 'blog')],
        );

        $result = $service->install('petya/blog');

        self::assertFalse($result->isSuccessful());
        self::assertStringContainsString('already held by the module "vasya/blog"', (string) $result->error());
    }

    public function testAModuleThisSiteCannotRunIsNotInstalled(): void
    {
        $service = $this->service(['vasya/blog' => ['requires' => new ModuleRequirements(johncms: '^99.0')]]);

        $result = $service->install('vasya/blog');

        self::assertFalse($result->isSuccessful());
        self::assertStringContainsString('Requires JohnCMS ^99.0', (string) $result->error());
    }

    public function testSwitchingOffKeepsTheDataAndSwitchingOnBringsTheModuleBack(): void
    {
        $service = $this->service(['vasya/blog' => []], state: ['vasya/blog' => new ModuleStateRecord('vasya/blog', 'blog')]);

        self::assertTrue($service->disable('vasya/blog')->isSuccessful());
        self::assertFalse((new ModuleStateStore($this->stateFile))->find('vasya/blog')?->enabled);
        self::assertSame([], $this->migratorCalls, 'Switching off touches no migrations.');

        self::assertTrue($service->enable('vasya/blog')->isSuccessful());
        self::assertTrue((new ModuleStateStore($this->stateFile))->find('vasya/blog')?->enabled);
    }

    public function testAModuleAnotherOneNeedsCannotBeSwitchedOff(): void
    {
        $service = $this->service(
            [
                'vasya/core' => [],
                'vasya/blog' => ['requires' => new ModuleRequirements(modules: ['vasya/core' => '^1.0'])],
            ],
            state: [
                'vasya/core' => new ModuleStateRecord('vasya/core', 'core', version: '1.0.0'),
                'vasya/blog' => new ModuleStateRecord('vasya/blog', 'blog', version: '1.0.0'),
            ],
        );

        $result = $service->disable('vasya/core');

        self::assertFalse($result->isSuccessful());
        self::assertStringContainsString('is needed by: vasya/blog', (string) $result->error());
    }

    public function testASystemModuleCannotBeSwitchedOffOrRemoved(): void
    {
        $service = $this->service(
            ['johncms/admin' => ['system' => true]],
            state: ['johncms/admin' => new ModuleStateRecord('johncms/admin', 'admin')],
        );

        self::assertStringContainsString('system module', (string) $service->disable('johncms/admin')->error());
        self::assertStringContainsString('system module', (string) $service->uninstall('johncms/admin')->error());
    }

    public function testUninstallingKeepsTheDataUnlessAskedToPurge(): void
    {
        $service = $this->service(['vasya/blog' => []], state: ['vasya/blog' => new ModuleStateRecord('vasya/blog', 'blog')]);

        $result = $service->uninstall('vasya/blog');

        self::assertTrue($result->isSuccessful());
        self::assertSame([], $this->migratorCalls, 'Without --purge nothing is rolled back.');
        self::assertNull((new ModuleStateStore($this->stateFile))->find('vasya/blog'));
    }

    public function testPurgingUndoesEveryMigrationOfTheModule(): void
    {
        $service = $this->service(['vasya/blog' => []], state: ['vasya/blog' => new ModuleStateRecord('vasya/blog', 'blog')]);

        self::assertTrue($service->uninstall('vasya/blog', purge: true)->isSuccessful());
        self::assertSame(['rollback', 'blog'], $this->migratorCalls[0] ?? []);
    }

    /**
     * Rolling a module back to the middle of its own history leaves a schema nobody can describe,
     * so the refusal has to come before anything is undone.
     */
    public function testAModuleThatCannotUndoItsMigrationsIsNotPurged(): void
    {
        $service = $this->service(['vasya/blog' => []], state: ['vasya/blog' => new ModuleStateRecord('vasya/blog', 'blog')]);
        $this->irreversibleMigrations = ['add_blog_counter'];

        $result = $service->uninstall('vasya/blog', purge: true);

        self::assertFalse($result->isSuccessful());
        self::assertStringContainsString('does not say how to undo add_blog_counter', (string) $result->error());
        self::assertSame([], $this->migratorCalls, 'Nothing was rolled back.');
        self::assertNotNull(
            (new ModuleStateStore($this->stateFile))->find('vasya/blog'),
            'The module is still installed, because its data is still there.'
        );
    }

    public function testUpdatingRunsTheMigrationsAndRecordsTheNewVersion(): void
    {
        $service = $this->service(
            ['vasya/blog' => ['version' => '2.0.0']],
            state: ['vasya/blog' => new ModuleStateRecord('vasya/blog', 'blog', version: '1.0.0')],
        );

        self::assertTrue($service->update('vasya/blog')->isSuccessful());
        self::assertSame(['run', 'blog'], $this->migratorCalls[0] ?? []);
        self::assertSame('2.0.0', (new ModuleStateStore($this->stateFile))->find('vasya/blog')?->version);
    }

    /**
     * The alias names the migrations of the module and the namespace of its templates. A new
     * version that renames it is not an update — it is a different module wearing the same key.
     */
    public function testAnUpdateThatRenamesTheAliasIsRefused(): void
    {
        $service = $this->service(
            ['vasya/blog' => ['alias' => 'renamed', 'version' => '2.0.0']],
            state: ['vasya/blog' => new ModuleStateRecord('vasya/blog', 'blog', version: '1.0.0')],
        );

        $result = $service->update('vasya/blog');

        self::assertFalse($result->isSuccessful());
        self::assertStringContainsString('cannot change', (string) $result->error());
    }

    /** @var MigrationRunnerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $migrator;

    /** @var list<array{0: string, 1: string|null}> */
    private array $migratorCalls = [];

    /**
     * @param array<string, array<string, mixed>> $modules
     * @param array<string, ModuleStateRecord>    $state
     */
    private function service(array $modules, array $state = []): ModuleInstallService
    {
        $manifests = [];
        foreach ($modules as $key => $options) {
            $manifests[$key] = new ModuleManifest(
                key: $key,
                alias: $options['alias'] ?? basename($key),
                path: $this->root . $key,
                name: ucfirst(basename($key)),
                version: $options['version'] ?? '1.0.0',
                system: $options['system'] ?? false,
                requires: $options['requires'] ?? new ModuleRequirements(),
            );
        }

        $repository = new class ($manifests) implements ModuleRepositoryInterface {
            /** @param array<string, ModuleManifest> $modules */
            public function __construct(private readonly array $modules)
            {
            }

            public function all(): array
            {
                return $this->modules;
            }

            public function find(string $key): ?ModuleManifest
            {
                return $this->modules[$key] ?? null;
            }

            public function forget(): void
            {
            }
        };

        $store = new ModuleStateStore($this->stateFile);
        if ($state !== []) {
            $store->save($state);
        }

        // Roles are not what these tests are about: the operations only ever hand permissions to
        // the two services below, and both are given a repository that holds nothing.
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $roles->method('all')->willReturn(new Collection());

        $this->migrator = $this->createMock(MigrationRunnerInterface::class);
        $this->migratorCalls = [];
        $this->migrator->method('run')->willReturnCallback(function (?string $source = null): array {
            $this->migratorCalls[] = ['run', $source];

            return [];
        });
        $this->migrator->method('rollback')->willReturnCallback(function (?string $source = null): array {
            $this->migratorCalls[] = ['rollback', $source];

            return [];
        });
        $this->migrator->method('irreversible')->willReturnCallback(
            fn (string $source): array => array_map(
                static fn (string $name): MigrationFile => new MigrationFile($source, '20260825110000', $name, ''),
                $this->irreversibleMigrations
            )
        );

        return new ModuleInstallService(
            new ModuleRegistry($repository, new ModuleStateStore($this->stateFile)),
            $repository,
            $store,
            $this->migrator,
            new ModuleCacheInvalidator($this->root . 'cache' . DS),
            new ModuleAssetPublisher($this->root . 'public' . DS),
            new RolePermissionPurger([], $roles, $this->root . 'backups'),
            new DefaultPermissionsApplier($roles, new DefaultPermissions(new PermissionRegistry())),
        );
    }
}
