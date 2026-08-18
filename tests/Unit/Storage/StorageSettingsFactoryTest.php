<?php

declare(strict_types=1);

namespace Tests\Unit\Storage;

use Johncms\Config\ConfigRepository;
use Johncms\Storage\StorageDriver;
use Johncms\Storage\StorageSettingsFactory;
use Johncms\Storage\UnsupportedStorageDriverException;
use PHPUnit\Framework\TestCase;

final class StorageSettingsFactoryTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $configBackup = [];

    protected function setUp(): void
    {
        $this->configBackup = ConfigRepository::all();
    }

    protected function tearDown(): void
    {
        ConfigRepository::init($this->configBackup);
    }

    public function testReadsTheDisksOutOfTheConfiguration(): void
    {
        $this->config([
            'default' => 'archive',
            'disks'   => [
                'archive' => [
                    'driver'     => 'local',
                    'root'       => '/var/archive',
                    'url'        => '/files',
                    'visibility' => 'private',
                ],
            ],
        ]);

        $settings = (new StorageSettingsFactory())();
        $disk = $settings->disk('archive');

        self::assertSame('archive', $settings->default);
        self::assertSame('archive', $disk->name);
        self::assertSame(StorageDriver::Local, $disk->driver);
        self::assertSame('/var/archive', $disk->root);
        self::assertSame('/files', $disk->url);
        self::assertSame('private', $disk->visibility);
    }

    public function testADiskIsPublicUnlessItSaysOtherwise(): void
    {
        $this->config(['disks' => ['local' => ['driver' => 'local', 'root' => '/var/upload']]]);

        self::assertSame('public', (new StorageSettingsFactory())()->disk('local')->visibility);
    }

    /**
     * The previous implementation had a single `default:` branch, so every unknown type quietly
     * became a local directory. A typo has to be an error instead.
     */
    public function testAnUnknownDriverIsRefused(): void
    {
        $this->config(['disks' => ['media' => ['driver' => 's3', 'root' => '/var/upload']]]);

        $this->expectException(UnsupportedStorageDriverException::class);
        $this->expectExceptionMessage('Unknown storage driver "s3" configured for the "media" disk.');

        (new StorageSettingsFactory())();
    }

    public function testADiskWithoutADriverIsRefused(): void
    {
        $this->config(['disks' => ['media' => ['root' => '/var/upload']]]);

        $this->expectException(UnsupportedStorageDriverException::class);
        $this->expectExceptionMessage('The "media" disk has no driver.');

        (new StorageSettingsFactory())();
    }

    /**
     * @param array<string, mixed> $filesystem
     */
    private function config(array $filesystem): void
    {
        ConfigRepository::init(['filesystem' => $filesystem]);
    }
}
