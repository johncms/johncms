<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Migrations;

use Johncms\Database\Migrations\ModuleMigrationSourceProvider;
use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleRepositoryInterface;
use Johncms\Modules\ModuleStateRecord;
use Johncms\Modules\ModuleStateStore;
use PHPUnit\Framework\TestCase;

/**
 * Which modules the schema of a site is made of.
 *
 * The answer is not "the ones that are loaded": switching a module off leaves its tables in the
 * database, and a journal that stops mentioning them would report a schema the site does not have.
 */
final class ModuleMigrationSourceProviderTest extends TestCase
{
    private string $stateFile;

    protected function setUp(): void
    {
        $this->stateFile = sys_get_temp_dir() . DS . 'johncms-sources-' . uniqid() . '.php';
    }

    protected function tearDown(): void
    {
        @unlink($this->stateFile);
    }

    public function testASourceIsNamedAfterTheAliasAndPointsAtTheMigrationsOfTheModule(): void
    {
        $sources = $this->provider(['johncms/forum' => 'forum'], bundled: ['johncms/forum'])->sources();

        self::assertCount(1, $sources);
        self::assertSame('forum', $sources[0]->name, 'The journal records the alias, not the key.');
        self::assertSame(MODULES_PATH . 'johncms/forum' . DS . 'migrations', $sources[0]->directory);
    }

    public function testASwitchedOffModuleIsStillPartOfTheSchema(): void
    {
        $provider = $this->provider(
            ['johncms/forum' => 'forum', 'johncms/news' => 'news'],
            bundled: ['johncms/forum', 'johncms/news'],
            state: ['johncms/news' => new ModuleStateRecord('johncms/news', 'news', enabled: false)],
        );

        self::assertSame(['forum', 'news'], array_map(static fn ($source) => $source->name, $provider->sources()));
    }

    /**
     * A module nobody installed has run no migrations, and it may not appear in the status of a
     * database it has never touched.
     */
    public function testAModuleThatWasNeverInstalledIsNotASource(): void
    {
        $provider = $this->provider(
            ['johncms/forum' => 'forum', 'vasya/blog' => 'blog'],
            bundled: ['johncms/forum'],
        );

        self::assertSame(['forum'], array_map(static fn ($source) => $source->name, $provider->sources()));
    }

    /**
     * @param array<string, string>            $modules Key to alias.
     * @param list<string>                     $bundled
     * @param array<string, ModuleStateRecord> $state
     */
    private function provider(array $modules, array $bundled = [], array $state = []): ModuleMigrationSourceProvider
    {
        $manifests = [];
        foreach ($modules as $key => $alias) {
            $manifests[$key] = new ModuleManifest(
                key: $key,
                alias: $alias,
                path: MODULES_PATH . $key,
                name: ucfirst($alias),
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
        };

        $store = new ModuleStateStore($this->stateFile);
        if ($state !== []) {
            $store->save($state);
        }

        return new ModuleMigrationSourceProvider(new ModuleRegistry($repository, $store, $bundled));
    }
}
