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

    /**
     * The package is optional, so the site that configures the driver without installing it has
     * to be told what to install — not left with a missing-class fatal out of the container.
     */
    public function testTheS3DriverSaysWhatToInstallWhenThePackageIsMissing(): void
    {
        if (class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class)) {
            self::markTestSkipped('league/flysystem-aws-s3-v3 is installed in this environment.');
        }

        $this->expectException(UnsupportedStorageDriverException::class);
        $this->expectExceptionMessage('composer require league/flysystem-aws-s3-v3');

        (new StorageFactory())->create(
            new DiskSettings(
                name: 'media',
                driver: StorageDriver::S3,
                options: ['bucket' => 'files', 'region' => 'eu-central-1'],
            )
        );
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
