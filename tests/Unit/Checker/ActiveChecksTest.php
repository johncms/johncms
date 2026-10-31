<?php

declare(strict_types=1);

namespace Tests\Unit\Checker;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Cache\CacheDriver;
use Johncms\Cache\CacheInterface;
use Johncms\Cache\CacheSettings;
use Johncms\Checker\CacheProbeInterface;
use Johncms\Checker\CacheRoundTripProbe;
use Johncms\Checker\Checks\CacheCheck;
use Johncms\Checker\Checks\StorageCheck;
use Johncms\Checker\CheckStatus;
use Johncms\Storage\DiskSettings;
use Johncms\Storage\StorageDriver;
use Johncms\Storage\StorageInterface;
use Johncms\Storage\StorageFactory;
use Johncms\Storage\StorageRegistry;
use Johncms\Storage\StorageRegistryInterface;
use Johncms\Storage\StorageSettings;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Tests\Support\InMemoryCache;

final class ActiveChecksTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
    }

    public function testTheCacheProbeRemovesItsDataAndPreservesOtherEntries(): void
    {
        $storage = new ArrayAdapter();
        $cache = InMemoryCache::over($storage);
        $cache->set('existing', 'keep');
        $keys = array_keys($storage->getValues());

        self::assertTrue((new CacheRoundTripProbe($cache))->roundTrip());
        self::assertSame('keep', $cache->get('existing'));
        self::assertSame($keys, array_keys($storage->getValues()));
    }

    public function testTheCacheProbeAttemptsCleanupAfterReadFailure(): void
    {
        // A failing backend is deliberately doubled; a healthy round trip uses the real cache.
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('set')->willReturn(true);
        $cache->method('get')->willThrowException(new RuntimeException('Unavailable backend'));
        $cache->expects(self::once())->method('delete')->with(self::stringStartsWith('system_check_'))->willReturn(true);

        $this->expectException(RuntimeException::class);
        (new CacheRoundTripProbe($cache))->roundTrip();
    }

    public function testDisabledCacheDoesNotRunAWriteProbe(): void
    {
        $probe = $this->createMock(CacheProbeInterface::class);
        $probe->expects(self::never())->method('roundTrip');
        $result = (new CacheCheck(new CacheSettings(driver: CacheDriver::Disabled), $probe))->run()[0];
        self::assertSame(CheckStatus::Skipped, $result->status);
    }

    public function testAStorageReadFailureStillRemovesTheFileAndContinuesWithTheNextDisk(): void
    {
        $broken = $this->createMock(StorageInterface::class);
        $broken->expects(self::once())->method('store');
        $broken->method('read')->willThrowException(new RuntimeException('Secret access credentials'));
        $broken->expects(self::once())->method('delete')->with(self::stringStartsWith('johncms-system-check-'));

        $stored = [];
        $working = $this->createMock(StorageInterface::class);
        $working->method('store')->willReturnCallback(static function (string $path, string $contents) use (&$stored): void {
            $stored[$path] = $contents;
        });
        $working->method('read')->willReturnCallback(static function (string $path) use (&$stored): string {
            return $stored[$path];
        });
        $working->expects(self::once())->method('delete')->willReturnCallback(static function (string $path) use (&$stored): void {
            unset($stored[$path]);
        });
        $registry = $this->createMock(StorageRegistryInterface::class);
        $registry->method('disk')->willReturnMap([['broken', $broken], ['working', $working]]);
        $settings = new StorageSettings(default: 'broken', disks: [
            'broken' => new DiskSettings('broken', StorageDriver::Local, '/broken'),
            'working' => new DiskSettings('working', StorageDriver::Local, '/working'),
        ]);

        $results = (new StorageCheck($settings, $registry, new NullLogger()))->run();

        self::assertSame([CheckStatus::Unavailable, CheckStatus::Passed], array_column($results, 'status'));
        self::assertStringNotContainsString('Secret', $results[0]->description);
        self::assertSame([], $stored);
    }

    public function testAStorageCleanupFailureIsNotReportedAsSuccess(): void
    {
        $disk = $this->createMock(StorageInterface::class);
        $disk->method('read')->willReturn('anything');
        $disk->method('delete')->willThrowException(new RuntimeException('Cannot delete'));
        $registry = $this->createMock(StorageRegistryInterface::class);
        $registry->method('disk')->willReturn($disk);
        $result = (new StorageCheck(new StorageSettings(), $registry, new NullLogger()))->run()[0];
        self::assertSame(CheckStatus::Unavailable, $result->status);
    }

    public function testTheStorageProbeUsesAndCleansARealLocalDisk(): void
    {
        $root = sys_get_temp_dir() . '/johncms-check-storage-' . bin2hex(random_bytes(8));
        mkdir($root);
        file_put_contents($root . '/existing.txt', 'keep');
        $settings = new StorageSettings(disks: ['local' => new DiskSettings('local', StorageDriver::Local, $root)]);

        try {
            $registry = new StorageRegistry($settings, new StorageFactory());
            $result = (new StorageCheck($settings, $registry, new NullLogger()))->run()[0];
            self::assertSame(CheckStatus::Passed, $result->status);
            self::assertSame(['.', '..', 'existing.txt'], scandir($root));
            self::assertSame('keep', file_get_contents($root . '/existing.txt'));
        } finally {
            foreach (glob($root . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($root);
        }
    }
}
