<?php

declare(strict_types=1);

namespace Tests\Unit\Storage;

use Johncms\Storage\DiskSettings;
use Johncms\Storage\StorageDriver;
use Johncms\Storage\StorageFactory;
use Johncms\Storage\StorageRegistry;
use Johncms\Storage\StorageSettings;
use Johncms\Storage\UnknownStorageDiskException;
use PHPUnit\Framework\TestCase;

final class StorageRegistryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'johncms-registry-' . bin2hex(random_bytes(6));
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        rmdir($this->root);
    }

    public function testWithoutANameItReturnsTheConfiguredDefaultDisk(): void
    {
        $registry = $this->registry(default: 'archive');

        self::assertSame($registry->disk('archive'), $registry->disk());
    }

    /**
     * The previous implementation defaulted to a hard-coded "local" and never read the
     * `default` key at all.
     */
    public function testTheDefaultIsNotHardCodedToLocal(): void
    {
        $registry = $this->registry(default: 'archive');

        self::assertNotSame($registry->disk('local'), $registry->disk());
    }

    public function testADiskIsBuiltOnceAndReused(): void
    {
        $registry = $this->registry();

        self::assertSame($registry->disk('local'), $registry->disk('local'));
    }

    public function testAnUnknownDiskNamesWhatIsConfigured(): void
    {
        $registry = $this->registry();

        $this->expectException(UnknownStorageDiskException::class);
        $this->expectExceptionMessage('Configured disks: local, archive.');

        $registry->disk('s3');
    }

    private function registry(string $default = 'local'): StorageRegistry
    {
        $settings = new StorageSettings(
            default: $default,
            disks: [
                'local'   => new DiskSettings(name: 'local', driver: StorageDriver::Local, root: $this->root),
                'archive' => new DiskSettings(name: 'archive', driver: StorageDriver::Local, root: $this->root),
            ],
        );

        return new StorageRegistry($settings, new StorageFactory());
    }
}
