<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleRepositoryInterface;
use Johncms\Modules\ModuleStateRecord;
use Johncms\Modules\ModuleStateStore;
use Johncms\Modules\ModuleStatus;
use PHPUnit\Framework\TestCase;

/**
 * What the site loads, decided from three things that disagree with each other all the time: the
 * directory, the list of the release and what the site recorded.
 *
 * The rule this is all built around is that nothing here may raise. A module whose files someone
 * deleted, or one whose alias collides with another, has to end up reported and left out — never
 * as an exception during the boot, which is a site that answers nothing at all.
 */
final class ModuleRegistryTest extends TestCase
{
    private string $stateFile;

    protected function setUp(): void
    {
        $this->stateFile = sys_get_temp_dir() . DS . 'johncms-registry-' . uniqid() . '.php';
    }

    protected function tearDown(): void
    {
        @unlink($this->stateFile);
    }

    public function testAModuleOfTheReleaseIsLoadedWithoutAnythingBeingRecorded(): void
    {
        $registry = $this->registry(
            onDisk: ['johncms/news' => $this->manifest('johncms/news', 'news')],
            bundled: ['johncms/news'],
        );

        self::assertSame(['johncms/news'], array_keys($registry->enabled()));
        self::assertSame(ModuleStatus::Enabled, $registry->find('johncms/news')?->status);
    }

    public function testAModuleSwitchedOffIsNotLoadedButStaysInstalled(): void
    {
        $registry = $this->registry(
            onDisk: ['johncms/news' => $this->manifest('johncms/news', 'news')],
            bundled: ['johncms/news'],
            state: ['johncms/news' => new ModuleStateRecord('johncms/news', 'news', enabled: false)],
        );

        self::assertSame([], array_keys($registry->enabled()));
        // Its tables are still there, so its migrations are still part of the schema of this site.
        self::assertSame(['johncms/news'], array_keys($registry->installed()));
        self::assertSame(ModuleStatus::Disabled, $registry->find('johncms/news')?->status);
    }

    /**
     * Files in the directory are not an installation: the migrations of the module have not run,
     * and loading its routes would be a module answering with tables that do not exist.
     */
    public function testAModuleThatWasOnlyDroppedIntoTheDirectoryIsNotLoaded(): void
    {
        $registry = $this->registry(onDisk: ['vasya/blog' => $this->manifest('vasya/blog', 'blog')]);

        self::assertSame([], array_keys($registry->enabled()));
        self::assertSame([], array_keys($registry->installed()));
        self::assertSame(ModuleStatus::Discovered, $registry->find('vasya/blog')?->status);
    }

    public function testAThirdPartyModuleIsLoadedOnceTheSiteRecordsIt(): void
    {
        $registry = $this->registry(
            onDisk: ['vasya/blog' => $this->manifest('vasya/blog', 'blog')],
            state: ['vasya/blog' => new ModuleStateRecord('vasya/blog', 'blog', version: '1.0.0')],
        );

        self::assertSame(['vasya/blog'], array_keys($registry->enabled()));
        self::assertSame('1.0.0', $registry->find('vasya/blog')?->version);
    }

    public function testAModuleWhoseFilesAreGoneIsReportedInsteadOfRaising(): void
    {
        $registry = $this->registry(
            onDisk: [],
            state: ['vasya/blog' => new ModuleStateRecord('vasya/blog', 'blog')],
        );

        $state = $registry->find('vasya/blog');

        self::assertNotNull($state);
        self::assertSame(ModuleStatus::Broken, $state->status);
        self::assertStringContainsString('no such module', (string) $state->problem);
        self::assertSame([], array_keys($registry->enabled()));
    }

    /**
     * The alias is a Twig namespace, a translation domain and the name of a migration source, so
     * two modules cannot share one. The installed module keeps it; the other one is refused —
     * which is not the same as being deleted, and says why in the listing.
     */
    public function testAModuleCannotTakeAnAliasAnInstalledOneAlreadyHolds(): void
    {
        $registry = $this->registry(
            onDisk: [
                'johncms/news' => $this->manifest('johncms/news', 'news'),
                'vasya/news'   => $this->manifest('vasya/news', 'news'),
            ],
            bundled: ['johncms/news'],
        );

        self::assertSame(['johncms/news'], array_keys($registry->enabled()));

        $intruder = $registry->find('vasya/news');
        self::assertNotNull($intruder);
        self::assertSame(ModuleStatus::Broken, $intruder->status);
        self::assertStringContainsString('already held by the module "johncms/news"', (string) $intruder->problem);
    }

    /**
     * Safe mode is the way back into a site a module has taken down: the panel and what it needs,
     * nothing else. Nothing is uninstalled by it — the modules come back when it is switched off.
     */
    public function testSafeModeLeavesTheSystemModulesOnly(): void
    {
        $registry = $this->registry(
            onDisk: [
                'johncms/admin' => $this->manifest('johncms/admin', 'admin', system: true),
                'johncms/news'  => $this->manifest('johncms/news', 'news'),
            ],
            bundled: ['johncms/admin', 'johncms/news'],
            safeMode: true,
        );

        self::assertSame(['johncms/admin'], array_keys($registry->enabled()));
        self::assertSame(
            ['johncms/admin', 'johncms/news'],
            array_keys($registry->installed()),
            'Safe mode does not uninstall anything.'
        );
    }

    /**
     * The alias recorded at installation wins over the one in the manifest: the journal of
     * migrations and the templates were written under it, and a manifest edited afterwards must
     * not silently move a running module to another namespace.
     */
    public function testTheRecordedAliasIsTheOneThatCounts(): void
    {
        $registry = $this->registry(
            onDisk: ['vasya/blog' => $this->manifest('vasya/blog', 'renamed')],
            state: ['vasya/blog' => new ModuleStateRecord('vasya/blog', 'blog')],
        );

        self::assertSame('blog', $registry->find('vasya/blog')?->alias);
    }

    /**
     * @param array<string, ModuleManifest> $onDisk
     * @param list<string>                  $bundled
     * @param array<string, ModuleStateRecord> $state
     */
    private function registry(array $onDisk = [], array $bundled = [], array $state = [], bool $safeMode = false): ModuleRegistry
    {
        $store = new ModuleStateStore($this->stateFile);
        if ($state !== []) {
            $store->save($state);
        }

        $repository = new class ($onDisk) implements ModuleRepositoryInterface {
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
        };

        return new ModuleRegistry($repository, $store, $bundled, $safeMode);
    }

    private function manifest(string $key, string $alias, bool $system = false): ModuleManifest
    {
        return new ModuleManifest(
            key: $key,
            alias: $alias,
            path: MODULES_PATH . str_replace('/', DS, $key),
            name: ucfirst(basename($key)),
            system: $system,
        );
    }
}
