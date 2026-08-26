<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Package;

use Johncms\Modules\Package\ModulePackageException;
use Johncms\Modules\Package\ModulePackageValidator;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * What an archive from an unknown author can do to a filesystem, and what is refused before a
 * single byte is written.
 *
 * None of this is exotic: an entry named ../../config writes outside the directory it was unpacked
 * into, a symlink points wherever its author chose, and a few kilobytes expand into everything the
 * disk has.
 */
final class ModulePackageValidatorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DS . 'johncms-package-' . uniqid() . DS;
        mkdir($this->root, 0o777, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    public function testAPackageIsAcceptedAndItsRootDirectoryNamed(): void
    {
        $archive = $this->archive([
            'blog-1.0.0/module.php'        => '<?php return ["key" => "vasya/blog"];',
            'blog-1.0.0/config/routes.php' => '<?php return static function () {};',
        ]);

        self::assertSame('blog-1.0.0', (new ModulePackageValidator())->validate($archive));
    }

    public function testAnEntryLeadingOutsideTheModuleIsRefused(): void
    {
        $archive = $this->archive([
            'blog/module.php'                      => '<?php return ["key" => "vasya/blog"];',
            'blog/../../../config/database.php'    => '<?php return ["stolen" => true];',
        ]);

        $this->expectException(ModulePackageException::class);
        $this->expectExceptionMessage('path leading outside the module');

        (new ModulePackageValidator())->validate($archive);
    }

    public function testAnAbsolutePathIsRefused(): void
    {
        $archive = $this->archive([
            'blog/module.php' => '<?php return ["key" => "vasya/blog"];',
            '/etc/cron.d/evil' => '* * * * * root sh',
        ]);

        $this->expectException(ModulePackageException::class);
        $this->expectExceptionMessage('absolute path');

        (new ModulePackageValidator())->validate($archive);
    }

    public function testAnArchiveWithoutAManifestIsNotAPackage(): void
    {
        $archive = $this->archive(['backup/notes.txt' => 'nothing to see']);

        $this->expectException(ModulePackageException::class);
        $this->expectExceptionMessage('no module.php');

        (new ModulePackageValidator())->validate($archive);
    }

    /**
     * A module is one directory. Two of them at the top is somebody's backup of the whole modules
     * folder, and unpacking it would put files where nobody asked.
     */
    public function testAnArchiveWithTwoRootDirectoriesIsRefused(): void
    {
        $archive = $this->archive([
            'blog/module.php' => '<?php return [];',
            'shop/module.php' => '<?php return [];',
        ]);

        $this->expectException(ModulePackageException::class);
        $this->expectExceptionMessage('exactly one directory');

        (new ModulePackageValidator())->validate($archive);
    }

    public function testAnArchiveThatUnpacksToTooMuchIsRefused(): void
    {
        $archive = $this->archive([
            'blog/module.php' => '<?php return ["key" => "vasya/blog"];',
            'blog/big.txt'    => str_repeat('a', 4096),
        ]);

        $this->expectException(ModulePackageException::class);
        $this->expectExceptionMessage('unpacks to more than');

        (new ModulePackageValidator(maxUnpackedBytes: 1024))->validate($archive);
    }

    public function testAnArchiveWithTooManyEntriesIsRefused(): void
    {
        $files = ['blog/module.php' => '<?php return ["key" => "vasya/blog"];'];
        for ($i = 0; $i < 5; ++$i) {
            $files['blog/file' . $i . '.txt'] = 'x';
        }

        $this->expectException(ModulePackageException::class);
        $this->expectExceptionMessage('more than a module has any business holding');

        (new ModulePackageValidator(maxEntries: 3))->validate($this->archive($files));
    }

    public function testAFileThatIsNotAnArchiveIsRefused(): void
    {
        $file = $this->root . 'notes.txt';
        file_put_contents($file, 'plain text');

        $this->expectException(ModulePackageException::class);
        $this->expectExceptionMessage('cannot be read as a zip archive');

        (new ModulePackageValidator())->validate($file);
    }

    /**
     * @param array<string, string> $files
     */
    private function archive(array $files): string
    {
        $path = $this->root . 'package-' . uniqid() . '.zip';

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);

        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        return $path;
    }
}
