<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use Johncms\Modules\FilesystemModuleRepository;
use Johncms\Modules\Manifest\ModuleManifestLoader;
use PHPUnit\Framework\TestCase;

/**
 * What the system finds on disk. The scan is two levels deep — a vendor, then a module — and it
 * has to survive whatever else ends up in that directory: an unpacked archive, a copy left by an
 * older layout, a stray file.
 */
final class FilesystemModuleRepositoryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DS . 'johncms-modules-' . uniqid() . DS;
        mkdir($this->root, 0o777, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    public function testModulesAreFoundUnderTheirVendorAndKeyedByTheirKey(): void
    {
        $this->module('johncms', 'forum', ['key' => 'johncms/forum', 'alias' => 'forum']);
        $this->module('vasya', 'blog', ['key' => 'vasya/blog', 'alias' => 'blog']);

        $modules = $this->repository()->all();

        self::assertSame(['johncms/forum', 'vasya/blog'], array_keys($modules));
        self::assertSame('forum', $modules['johncms/forum']->alias);
        self::assertSame($this->root . 'vasya' . DS . 'blog', $modules['vasya/blog']->path);
    }

    /**
     * Two vendors may ship a module of the same name — that is the whole point of the vendor
     * level, and neither of them has to know about the other.
     */
    public function testTwoVendorsMayShipAModuleOfTheSameName(): void
    {
        $this->module('vasya', 'blog', ['key' => 'vasya/blog', 'alias' => 'vasya.blog']);
        $this->module('petya', 'blog', ['key' => 'petya/blog', 'alias' => 'petya.blog']);

        self::assertSame(['petya/blog', 'vasya/blog'], array_keys($this->repository()->all()));
    }

    public function testADirectoryWithoutAManifestIsNotAModule(): void
    {
        $this->module('johncms', 'forum', ['key' => 'johncms/forum']);
        mkdir($this->root . 'johncms' . DS . 'leftovers', 0o777, true);
        file_put_contents($this->root . 'johncms' . DS . 'leftovers' . DS . 'readme.txt', 'nothing');

        self::assertSame(['johncms/forum'], array_keys($this->repository()->all()));
    }

    /**
     * A module dropped in the way the previous layout expected — modules/blog/ — is not picked up.
     * The scan starts one level lower, and a directory of modules is never a module itself.
     */
    public function testAModuleLeftInTheOldOneLevelLayoutIsNotFound(): void
    {
        mkdir($this->root . 'blog', 0o777, true);
        file_put_contents(
            $this->root . 'blog' . DS . 'module.php',
            '<?php return ' . var_export(['key' => 'vasya/blog'], true) . ';'
        );

        self::assertSame([], $this->repository()->all());
    }

    public function testAModuleThatIsNotThereIsNotFound(): void
    {
        $this->module('johncms', 'forum', ['key' => 'johncms/forum']);

        $repository = $this->repository();

        self::assertNotNull($repository->find('johncms/forum'));
        self::assertNull($repository->find('johncms/blog'));
    }

    /**
     * The manifests of this installation, read the way the system reads them. Cheap, and the only
     * thing standing between a typo in one of the 21 files and a module that stops existing.
     */
    public function testEveryModuleShippedWithTheCmsHasAReadableManifest(): void
    {
        $modules = (new FilesystemModuleRepository())->all();

        self::assertNotEmpty($modules);

        foreach ($modules as $key => $manifest) {
            self::assertSame($key, $manifest->key);
            self::assertSame('johncms/' . $manifest->alias, $key, 'The alias of a bundled module is its name.');
            self::assertSame(MODULES_PATH . str_replace('/', DS, $key), $manifest->path);
            self::assertNull($manifest->version, 'A bundled module has the version of the CMS.');
        }

        self::assertTrue($modules['johncms/admin']->system, 'The admin panel cannot be switched off.');
        self::assertFalse($modules['johncms/news']->system);
    }

    private function repository(): FilesystemModuleRepository
    {
        return new FilesystemModuleRepository(new ModuleManifestLoader(), $this->root);
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function module(string $vendor, string $name, array $manifest): void
    {
        $directory = $this->root . $vendor . DS . $name;
        mkdir($directory, 0o777, true);
        file_put_contents($directory . DS . 'module.php', '<?php return ' . var_export($manifest, true) . ';');
    }
}
