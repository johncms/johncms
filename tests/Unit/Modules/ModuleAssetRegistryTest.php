<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use Johncms\Modules\Manifest\ModuleAssets;
use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\Modules\ModuleAssetRegistry;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleRepositoryInterface;
use Johncms\Modules\ModuleStateRecord;
use Johncms\Modules\ModuleStateStore;
use PHPUnit\Framework\TestCase;

/**
 * What the layout loads because of the modules. Published files alone do nothing — a page has to
 * ask for them, and the page belongs to the theme.
 */
final class ModuleAssetRegistryTest extends TestCase
{
    private string $stateFile;

    protected function setUp(): void
    {
        $this->stateFile = sys_get_temp_dir() . DS . 'johncms-asset-registry-' . uniqid() . '.php';
    }

    protected function tearDown(): void
    {
        @unlink($this->stateFile);
    }

    public function testTheEntriesOfTheLoadedModulesAreListedForTheirArea(): void
    {
        $registry = $this->registry([
            'vasya/blog' => ['public' => ['js/blog.js'], 'admin' => ['js/blog-admin.js']],
            'petya/shop' => ['public' => ['css/shop.css']],
        ]);

        // The order is the order the modules load in — by key, so it is the same on every run.
        self::assertSame(
            ['/modules/shop/css/shop.css?v=1.0.0', '/modules/blog/js/blog.js?v=1.0.0'],
            $registry->urls('public')
        );
        self::assertSame(['/modules/blog/js/blog-admin.js?v=1.0.0'], $registry->urls('admin'));
    }

    public function testASwitchedOffModuleAsksForNothing(): void
    {
        $registry = $this->registry(
            ['vasya/blog' => ['public' => ['js/blog.js']]],
            enabled: false,
        );

        self::assertSame([], $registry->urls('public'));
    }

    /**
     * The version of the module is the cache buster, so a browser holding the previous file lets
     * go of it after an update. A module of the release has no version of its own and takes the
     * version of the CMS.
     */
    public function testTheVersionOfTheModuleIsAppendedToTheUrl(): void
    {
        $registry = $this->registry(['johncms/news' => ['public' => ['js/news.js']]], version: null);

        self::assertSame(['/modules/news/js/news.js?v=' . CMS_VERSION], $registry->urls('public'));
        self::assertSame('/modules/news/img/a.png?v=' . CMS_VERSION, $registry->url('news', 'img/a.png'));
    }

    /**
     * @param array<string, array<string, list<string>>> $modules Key to its entries by area.
     */
    private function registry(array $modules, bool $enabled = true, ?string $version = '1.0.0'): ModuleAssetRegistry
    {
        $manifests = [];
        $records = [];
        foreach ($modules as $key => $entries) {
            $alias = basename($key);
            $manifests[$key] = new ModuleManifest(
                key: $key,
                alias: $alias,
                path: MODULES_PATH . $key,
                name: ucfirst($alias),
                version: $version,
                assets: new ModuleAssets(entries: $entries),
            );
            $records[$key] = new ModuleStateRecord($key, $alias, enabled: $enabled, version: $version);
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
        $store->save($records);

        return new ModuleAssetRegistry(new ModuleRegistry($repository, $store));
    }
}
