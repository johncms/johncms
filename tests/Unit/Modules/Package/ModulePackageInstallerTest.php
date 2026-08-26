<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Package;

use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\Modules\ModuleRepositoryInterface;
use Johncms\Modules\Package\ModulePackageException;
use Johncms\Modules\Package\ModulePackageInstaller;
use Johncms\Modules\Package\ModulePackageValidator;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Getting the files of a module out of an archive and into modules/.
 *
 * Written so that a failure at any point leaves the site as it was: nothing is unpacked into
 * modules/ directly, because a half-written module would be loaded by the next request that comes
 * in while the copying is still going on.
 */
final class ModulePackageInstallerTest extends TestCase
{
    private string $root;

    private string $modulesPath;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DS . 'johncms-pkg-install-' . uniqid() . DS;
        $this->modulesPath = $this->root . 'modules' . DS;
        mkdir($this->modulesPath, 0o777, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    public function testTheModuleEndsUpWhereItsKeySaysItBelongs(): void
    {
        $archive = $this->package('blog-1.0.0', 'vasya/blog', ['src/Blog.php' => '<?php class Blog {}']);

        $key = $this->installer()->extract($archive);

        self::assertSame('vasya/blog', $key);
        self::assertFileExists($this->modulesPath . 'vasya/blog/module.php');
        self::assertFileExists($this->modulesPath . 'vasya/blog/src/Blog.php');
        // The name of the directory inside the archive means nothing: the key decides.
        self::assertDirectoryDoesNotExist($this->modulesPath . 'blog-1.0.0');
    }

    public function testTheKeyCanBeReadWithoutInstallingAnything(): void
    {
        $archive = $this->package('blog-1.0.0', 'vasya/blog');

        self::assertSame('vasya/blog', $this->installer()->extractKeyOnly($archive));
        self::assertDirectoryDoesNotExist($this->modulesPath . 'vasya');
    }

    /**
     * An archive of a module the site already has replaces it — and the previous version is moved
     * aside first, because "install the new one" must not mean "lose the old one".
     */
    public function testThePreviousVersionIsKeptAside(): void
    {
        mkdir($this->modulesPath . 'vasya/blog', 0o777, true);
        file_put_contents($this->modulesPath . 'vasya/blog/module.php', '<?php return ["key" => "vasya/blog"];');
        file_put_contents($this->modulesPath . 'vasya/blog/old.txt', 'the previous version');

        $this->installer()->extract($this->package('blog-2.0.0', 'vasya/blog', ['new.txt' => 'the new one']));

        self::assertFileExists($this->modulesPath . 'vasya/blog/new.txt');
        self::assertFileDoesNotExist($this->modulesPath . 'vasya/blog/old.txt');

        $backups = (array) glob($this->root . 'backups' . DS . 'module-vasya-blog-*');
        self::assertCount(1, $backups);
        self::assertFileExists((string) $backups[0] . DS . 'old.txt');
    }

    /**
     * A module of the release is part of the CMS: replacing it from an archive would put a
     * different version of half the site in place, and the next upgrade would silently undo it.
     */
    public function testASystemModuleCannotBeReplacedFromAnArchive(): void
    {
        $installer = $this->installer(system: true);

        $this->expectException(ModulePackageException::class);
        $this->expectExceptionMessage('is a system module');

        $installer->extract($this->package('admin', 'johncms/admin'));
    }

    public function testNothingIsLeftBehindWhenTheArchiveIsRefused(): void
    {
        $archive = $this->package('blog', 'vasya/blog', ['../escape.txt' => 'nope']);

        try {
            $this->installer()->extract($archive);
            self::fail('The archive should have been refused.');
        } catch (ModulePackageException) {
            // expected
        }

        self::assertDirectoryDoesNotExist($this->modulesPath . 'vasya');
        self::assertSame([], (array) glob($this->root . 'tmp' . DS . 'modules' . DS . '*'));
    }

    private function installer(bool $system = false): ModulePackageInstaller
    {
        $repository = new class ($system) implements ModuleRepositoryInterface {
            public function __construct(private readonly bool $system)
            {
            }

            public function all(): array
            {
                return [];
            }

            public function find(string $key): ?ModuleManifest
            {
                return $this->system
                    ? new ModuleManifest($key, basename($key), '', 'Admin', system: true)
                    : null;
            }

            public function forget(): void
            {
            }
        };

        return new ModulePackageInstaller(
            new ModulePackageValidator(),
            $repository,
            modulesPath: $this->modulesPath,
            temporaryPath: $this->root . 'tmp',
            backupPath: $this->root . 'backups',
        );
    }

    /**
     * @param array<string, string> $files
     */
    private function package(string $root, string $key, array $files = []): string
    {
        $path = $this->root . 'package-' . uniqid() . '.zip';

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString($root . '/module.php', sprintf('<?php return ["key" => "%s"];', $key));

        foreach ($files as $name => $contents) {
            $zip->addFromString($root . '/' . $name, $contents);
        }

        $zip->close();

        return $path;
    }
}
