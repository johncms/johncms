<?php

declare(strict_types=1);

namespace Tests\Unit\Checker;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Cache\CacheSettings;
use Johncms\Checker\Checks\DirectoriesCheck;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\FilesystemProbeInterface;
use Johncms\Checker\NativeFilesystemProbe;
use Johncms\Checker\PhpEnvironmentInterface;
use Johncms\Checker\WorkingDirectories;
use Johncms\Modules\Admin\Application\SystemChecks\DiskSpaceCheck;
use Johncms\Modules\Admin\Domain\Services\ServerHealth;
use Johncms\Storage\DiskSettings;
use Johncms\Storage\StorageDriver;
use Johncms\Storage\StorageSettings;
use PHPUnit\Framework\TestCase;

final class FilesystemChecksTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
    }

    public function testAMissingDirectoryIsCheckedWithoutCreatingIt(): void
    {
        $path = sys_get_temp_dir() . '/johncms-check-' . bin2hex(random_bytes(8)) . '/nested';
        self::assertTrue((new NativeFilesystemProbe())->canWriteDirectory($path));
        self::assertDirectoryDoesNotExist(dirname($path));
    }

    public function testAnExistingFileCannotBeUsedAsAWorkingDirectory(): void
    {
        self::assertFalse((new NativeFilesystemProbe())->canWriteDirectory(__FILE__));
        self::assertFalse((new NativeFilesystemProbe())->canWriteDirectory(__FILE__ . '/nested'));
        self::assertFalse((new NativeFilesystemProbe())->canWriteDirectory(''));
    }

    public function testConfiguredCacheAndLocalDisksAreChecked(): void
    {
        $filesystem = $this->createMock(FilesystemProbeInterface::class);
        $filesystem->method('canWriteDirectory')->willReturnCallback(static fn (string $path): bool => $path !== '/media');

        $results = (new DirectoriesCheck($this->directories(), $filesystem))->run();
        $failed = array_values(array_filter($results, static fn ($result): bool => $result->status === CheckStatus::Failed));

        self::assertSame(['filesystem.directory.storage.media'], array_column($failed, 'id'));
        self::assertContains('/separate-cache', array_column($results, 'name'));
        self::assertNotContains('storage.remote', array_keys($this->directories()->all()));
    }

    public function testUnmeasurableDiskSpaceIsNotReportedAsHealthy(): void
    {
        $filesystem = $this->createMock(FilesystemProbeInterface::class);
        $filesystem->method('diskSpace')->willReturn(null);
        $results = (new DiskSpaceCheck($this->directories(), $filesystem, new ServerHealth()))->run();

        self::assertNotEmpty($results);
        self::assertSame([CheckStatus::Unavailable], array_values(array_unique(array_column($results, 'status'), SORT_REGULAR)));
    }

    public function testTheDiskThresholdMatchesTheDashboard(): void
    {
        $filesystem = $this->createMock(FilesystemProbeInterface::class);
        $filesystem->method('diskSpace')->willReturn(['free' => 9, 'total' => 100]);
        $results = (new DiskSpaceCheck($this->directories(), $filesystem, new ServerHealth()))->run();
        self::assertSame(CheckStatus::Failed, $results[0]->status);
    }

    private function directories(): WorkingDirectories
    {
        $php = $this->createMock(PhpEnvironmentInterface::class);
        $php->method('ini')->willReturn('/upload-tmp');

        return new WorkingDirectories(
            new StorageSettings(disks: [
                'media' => new DiskSettings('media', StorageDriver::Local, '/media'),
                'remote' => new DiskSettings('remote', StorageDriver::S3),
            ]),
            new CacheSettings(directory: '/separate-cache'),
            $php,
        );
    }
}
