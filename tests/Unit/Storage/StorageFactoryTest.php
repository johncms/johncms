<?php

declare(strict_types=1);

namespace Tests\Unit\Storage;

use Johncms\Storage\DiskSettings;
use Johncms\Storage\StorageDriver;
use Johncms\Storage\StorageFactory;
use Johncms\Storage\UnsupportedStorageDriverException;
use PHPUnit\Framework\TestCase;

final class StorageFactoryTest extends TestCase
{
    public function testALocalDiskWithoutARootIsRefused(): void
    {
        $this->expectException(UnsupportedStorageDriverException::class);
        $this->expectExceptionMessage('The local disk "broken" has no root directory.');

        (new StorageFactory())->create(new DiskSettings(name: 'broken', driver: StorageDriver::Local));
    }

    public function testTheDiskWritesUnderTheConfiguredRoot(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'johncms-factory-' . bin2hex(random_bytes(6));

        $disk = (new StorageFactory())->create(
            new DiskSettings(name: 'local', driver: StorageDriver::Local, root: $root)
        );
        $disk->store('file.txt', 'contents');

        try {
            self::assertFileExists($root . DIRECTORY_SEPARATOR . 'file.txt');
        } finally {
            unlink($root . DIRECTORY_SEPARATOR . 'file.txt');
            rmdir($root);
        }
    }
}
