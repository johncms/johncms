<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Manifest;

use Johncms\Modules\Exceptions\InvalidModuleManifestException;
use Johncms\Modules\Manifest\ModuleManifestLoader;
use PHPUnit\Framework\TestCase;

/**
 * The manifest is the only thing the system knows about a module before anything of it is loaded,
 * so a mistake in one has to be reported as a mistake in that file — not as a module that quietly
 * does not exist.
 */
final class ModuleManifestLoaderTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DS . 'johncms-manifests-' . uniqid() . DS;
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    public function testAManifestDescribesTheModuleItLiesIn(): void
    {
        $directory = $this->module('vasya', 'blog', [
            'key'     => 'vasya/blog',
            'alias'   => 'blog',
            'name'    => 'Blog',
            'version' => '1.2.0',
        ]);

        $manifest = (new ModuleManifestLoader())->load($directory);

        self::assertSame('vasya/blog', $manifest->key);
        self::assertSame('blog', $manifest->alias);
        self::assertSame('Blog', $manifest->name);
        self::assertSame('1.2.0', $manifest->version);
        self::assertSame($directory, $manifest->path);
        self::assertFalse($manifest->system);
    }

    /**
     * A module shipped with the CMS has no version of its own: it is the version of the CMS, and
     * such a module can never be out of date on its own.
     */
    public function testAModuleWithoutAVersionCarriesNone(): void
    {
        $directory = $this->module('johncms', 'forum', ['key' => 'johncms/forum', 'alias' => 'forum']);

        self::assertNull((new ModuleManifestLoader())->load($directory)->version);
    }

    /**
     * The alias is what a Twig namespace and a dictionary are named after, so every module has to
     * have one. Said nothing about, it is the key with the slash replaced — long, but free.
     */
    public function testAnAliasThatIsNotDeclaredIsBuiltFromTheKey(): void
    {
        $directory = $this->module('vasya', 'blog', ['key' => 'vasya/blog']);

        $manifest = (new ModuleManifestLoader())->load($directory);

        self::assertSame('vasya.blog', $manifest->alias);
        // Nothing to call it by either: the name of the directory, capitalised.
        self::assertSame('Blog', $manifest->name);
    }

    public function testASystemModuleSaysSo(): void
    {
        $directory = $this->module('johncms', 'admin', ['key' => 'johncms/admin', 'system' => true]);

        self::assertTrue((new ModuleManifestLoader())->load($directory)->system);
    }

    /**
     * The key is the identity of the module and the place it lies in at the same time. A manifest
     * where the two disagree is the half-installed module: found by the registry under one name,
     * missed by everything that resolves a path from it.
     */
    public function testAKeyThatNamesAnotherPlaceIsRefused(): void
    {
        $directory = $this->module('vasya', 'blog', ['key' => 'petya/blog']);

        $this->expectException(InvalidModuleManifestException::class);
        $this->expectExceptionMessage('lies in "vasya/blog"');

        (new ModuleManifestLoader())->load($directory);
    }

    public function testAKeyThatIsNotVendorAndNameIsRefused(): void
    {
        $directory = $this->module('vasya', 'blog', ['key' => 'blog']);

        $this->expectException(InvalidModuleManifestException::class);
        $this->expectExceptionMessage('not of the form vendor/name');

        (new ModuleManifestLoader())->load($directory);
    }

    public function testAManifestWithoutAKeyIsRefused(): void
    {
        $directory = $this->module('vasya', 'blog', ['name' => 'Blog']);

        $this->expectException(InvalidModuleManifestException::class);
        $this->expectExceptionMessage('declares no key');

        (new ModuleManifestLoader())->load($directory);
    }

    /**
     * Twig resolves "@namespace/file" by cutting the name at the first slash, so an alias holding
     * one would address a namespace nobody registered.
     */
    public function testAnAliasWithASlashIsRefused(): void
    {
        $directory = $this->module('vasya', 'blog', ['key' => 'vasya/blog', 'alias' => 'vasya/blog']);

        $this->expectException(InvalidModuleManifestException::class);
        $this->expectExceptionMessage('declares the alias "vasya/blog"');

        (new ModuleManifestLoader())->load($directory);
    }

    /**
     * A module installed into a site cannot put its namespace in the root composer.json, so it
     * says here what to register. A module of the release declares nothing and stays in Composer.
     */
    public function testAModuleMayDeclareWhatToAutoload(): void
    {
        $directory = $this->module('vasya', 'blog', [
            'key'      => 'vasya/blog',
            'autoload' => [
                'psr-4' => ['Vasya\\Blog\\' => 'src/'],
                'files' => ['/vendor/autoload.php'],
            ],
        ]);

        $autoload = (new ModuleManifestLoader())->load($directory)->autoload;

        // Both are stored relative to the module, without the leading or trailing slash.
        self::assertSame(['Vasya\\Blog\\' => 'src'], $autoload->psr4);
        self::assertSame(['vendor/autoload.php'], $autoload->files);
        self::assertFalse($autoload->isEmpty());
    }

    public function testAModuleThatDeclaresNoAutoloadCarriesNone(): void
    {
        $directory = $this->module('johncms', 'news', ['key' => 'johncms/news']);

        self::assertTrue((new ModuleManifestLoader())->load($directory)->autoload->isEmpty());
    }

    /**
     * PSR-4 resolves a prefix to a directory by cutting it off the class name, and a prefix
     * without its trailing separator cuts off one character too few.
     */
    public function testAPsr4PrefixWithoutATrailingBackslashIsRefused(): void
    {
        $directory = $this->module('vasya', 'blog', [
            'key'      => 'vasya/blog',
            'autoload' => ['psr-4' => ['Vasya\\Blog' => 'src/']],
        ]);

        $this->expectException(InvalidModuleManifestException::class);
        $this->expectExceptionMessage('must end with a backslash');

        (new ModuleManifestLoader())->load($directory);
    }

    public function testAnAutoloadThatIsNotAnArrayIsRefused(): void
    {
        $directory = $this->module('vasya', 'blog', ['key' => 'vasya/blog', 'autoload' => 'src/']);

        $this->expectException(InvalidModuleManifestException::class);
        $this->expectExceptionMessage('the "autoload" field must be an array');

        (new ModuleManifestLoader())->load($directory);
    }

    public function testAFieldOfTheWrongTypeIsRefused(): void
    {
        $directory = $this->module('vasya', 'blog', ['key' => 'vasya/blog', 'system' => 'yes']);

        $this->expectException(InvalidModuleManifestException::class);
        $this->expectExceptionMessage('the "system" field must be true or false');

        (new ModuleManifestLoader())->load($directory);
    }

    public function testAManifestThatReturnsSomethingElseIsRefused(): void
    {
        $directory = $this->root . 'vasya' . DS . 'blog';
        mkdir($directory, 0o777, true);
        file_put_contents($directory . DS . 'module.php', '<?php return "blog";');

        $this->expectException(InvalidModuleManifestException::class);
        $this->expectExceptionMessage('must return an array');

        (new ModuleManifestLoader())->load($directory);
    }

    public function testADirectoryWithoutAManifestIsRefused(): void
    {
        $directory = $this->root . 'vasya' . DS . 'blog';
        mkdir($directory, 0o777, true);

        $this->expectException(InvalidModuleManifestException::class);
        $this->expectExceptionMessage('has no module.php');

        (new ModuleManifestLoader())->load($directory);
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function module(string $vendor, string $name, array $manifest): string
    {
        $directory = $this->root . $vendor . DS . $name;
        mkdir($directory, 0o777, true);
        file_put_contents($directory . DS . 'module.php', '<?php return ' . var_export($manifest, true) . ';');

        return $directory;
    }
}
