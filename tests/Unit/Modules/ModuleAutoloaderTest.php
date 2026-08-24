<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use Composer\Autoload\ClassLoader;
use Johncms\Modules\Manifest\ModuleAutoload;
use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\Modules\ModuleAutoloader;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleRepositoryInterface;
use Johncms\Modules\ModuleStateRecord;
use Johncms\Modules\ModuleStateStore;
use PHPUnit\Framework\TestCase;

/**
 * What makes a module installable at all: its classes are found without the root composer.json
 * being touched. Rewriting that file from the outside is how an upgrade of the CMS loses the list
 * of what a site installed.
 */
final class ModuleAutoloaderTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DS . 'johncms-autoload-' . uniqid() . DS;
        mkdir($this->root, 0o777, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    public function testTheNamespaceOfAnInstalledModuleIsRegistered(): void
    {
        $manifest = $this->manifest('vasya/blog', new ModuleAutoload(psr4: ['Vasya\\Blog\\' => 'src']));
        $loader = new ClassLoader();

        $this->autoloader($loader, [$manifest], installed: true)->register();

        self::assertSame(
            [$this->root . 'vasya/blog' . DS . 'src'],
            $loader->getPrefixesPsr4()['Vasya\\Blog\\'] ?? []
        );
    }

    /**
     * A module that is switched off keeps its files and loses its classes. Without that, its
     * controllers would still be constructible and its listeners still callable — the module would
     * only look switched off.
     */
    public function testTheNamespaceOfASwitchedOffModuleIsNotRegistered(): void
    {
        $manifest = $this->manifest('vasya/blog', new ModuleAutoload(psr4: ['Vasya\\Blog\\' => 'src']));
        $loader = new ClassLoader();

        $this->autoloader($loader, [$manifest], installed: true, enabled: false)->register();

        self::assertArrayNotHasKey('Vasya\\Blog\\', $loader->getPrefixesPsr4());
    }

    public function testAModuleOfTheReleaseRegistersNothing(): void
    {
        // Its namespace is in the root composer.json, where Composer builds a classmap for it.
        $manifest = $this->manifest('johncms/news', new ModuleAutoload());
        $loader = new ClassLoader();

        $this->autoloader($loader, [$manifest], installed: true)->register();

        self::assertSame([], $loader->getPrefixesPsr4());
    }

    /**
     * A module that brings dependencies of its own points at its vendor/autoload.php, and that is
     * the only way they get loaded: Composer of the site knows nothing about them.
     */
    public function testTheFilesOfAModuleAreRequired(): void
    {
        $directory = $this->root . 'vasya/blog';
        mkdir($directory . DS . 'vendor', 0o777, true);
        file_put_contents(
            $directory . DS . 'vendor' . DS . 'autoload.php',
            '<?php $GLOBALS["johncms_test_vendor_loaded"] = true;'
        );

        $manifest = $this->manifest('vasya/blog', new ModuleAutoload(files: ['vendor/autoload.php']));

        $this->autoloader(new ClassLoader(), [$manifest], installed: true)->register();

        self::assertTrue($GLOBALS['johncms_test_vendor_loaded'] ?? false);
        unset($GLOBALS['johncms_test_vendor_loaded']);
    }

    /**
     * A package that points at a file it forgot to ship is broken, but the site is not: the
     * modules after it are still registered, and the missing classes surface where they are used.
     */
    public function testAMissingFileDoesNotStopTheModulesAfterIt(): void
    {
        $loader = new ClassLoader();

        $this->autoloader(
            $loader,
            [
                // Alphabetically first, so it is registered before the one that has to survive it.
                $this->manifest('aaa/broken', new ModuleAutoload(files: ['vendor/autoload.php'])),
                $this->manifest('vasya/blog', new ModuleAutoload(psr4: ['Vasya\\Blog\\' => 'src'])),
            ],
            installed: true
        )->register();

        self::assertArrayHasKey('Vasya\\Blog\\', $loader->getPrefixesPsr4());
    }

    /**
     * @param list<ModuleManifest> $manifests
     */
    private function autoloader(ClassLoader $loader, array $manifests, bool $installed, bool $enabled = true): ModuleAutoloader
    {
        $modules = [];
        foreach ($manifests as $manifest) {
            $modules[$manifest->key] = $manifest;
        }

        $repository = new class ($modules) implements ModuleRepositoryInterface {
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

        $store = new ModuleStateStore($this->root . 'state.php');
        if ($installed) {
            $records = [];
            foreach ($modules as $key => $manifest) {
                $records[$key] = new ModuleStateRecord($key, $manifest->alias, enabled: $enabled);
            }
            $store->save($records);
        }

        return new ModuleAutoloader($loader, new ModuleRegistry($repository, $store));
    }

    private function manifest(string $key, ModuleAutoload $autoload): ModuleManifest
    {
        return new ModuleManifest(
            key: $key,
            alias: basename($key),
            path: $this->root . $key,
            name: ucfirst(basename($key)),
            autoload: $autoload,
        );
    }
}
