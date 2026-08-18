<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use Johncms\Cache\CacheDriver;
use Johncms\Cache\CachePoolFactory;
use Johncms\Cache\CacheSettings;
use Johncms\Cache\TagsStorage;
use Johncms\Cache\UnsupportedCacheDriverException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\PruneableInterface;

final class CachePoolFactoryTest extends TestCase
{
    private string $directory = '';

    protected function tearDown(): void
    {
        if ($this->directory !== '') {
            $this->removeDirectory($this->directory);
            $this->directory = '';
        }

        parent::tearDown();
    }

    /**
     * Tagging an item on a pool that is not tag-aware throws, so a module tagging its entries
     * would break on whichever driver the site happens to be configured with. Every driver has
     * to come out of the factory tag-aware.
     *
     * @param CacheDriver $driver A driver that needs no extension to run in the test suite.
     */
    #[DataProvider('driversAvailableEverywhere')]
    public function testEveryDriverSupportsTags(CacheDriver $driver): void
    {
        $cache = $this->factory($driver)->create();

        $cache->rememberForever('key', fn (): string => 'value', ['news']);

        self::assertTrue($cache->invalidateTags('news'));
    }

    /**
     * @return iterable<string, array{CacheDriver}>
     */
    public static function driversAvailableEverywhere(): iterable
    {
        yield 'filesystem' => [CacheDriver::Filesystem];
        yield 'array' => [CacheDriver::Memory];
        yield 'null' => [CacheDriver::Disabled];
    }

    /**
     * Symlinks are the faster way to relate a tag to its entries, and the one some shared
     * hostings and Windows do not offer. Both paths have to invalidate the same way.
     *
     * @param TagsStorage $storage How the filesystem driver keeps that relation.
     */
    #[DataProvider('filesystemTagStorages')]
    public function testTheFilesystemDriverInvalidatesTagsWithAndWithoutSymlinks(TagsStorage $storage): void
    {
        $cache = $this->factory(CacheDriver::Filesystem, $storage)->create();

        $cache->rememberForever('tagged', fn (): string => 'first', ['news']);
        $cache->rememberForever('untagged', fn (): string => 'kept');
        $cache->invalidateTags('news');

        self::assertSame('second', $cache->rememberForever('tagged', fn (): string => 'second', ['news']));
        self::assertSame('kept', $cache->rememberForever('untagged', fn (): string => 'lost'));
    }

    /**
     * @return iterable<string, array{TagsStorage}>
     */
    public static function filesystemTagStorages(): iterable
    {
        yield 'auto' => [TagsStorage::Auto];
        yield 'symlink' => [TagsStorage::Symlink];
        yield 'files' => [TagsStorage::Files];
    }

    /**
     * The probe costs a symlink call, so its answer is kept next to the cache instead of being
     * repeated on every request.
     */
    public function testTheSymlinkProbeRunsOnceAndRemembersItsAnswer(): void
    {
        $factory = $this->factory(CacheDriver::Filesystem, TagsStorage::Auto);
        $factory->createPool();

        self::assertFileExists($this->directory . DIRECTORY_SEPARATOR . '.symlinks');
        self::assertFileDoesNotExist($this->directory . DIRECTORY_SEPARATOR . '.symlinks-probe');
        self::assertFileDoesNotExist($this->directory . DIRECTORY_SEPARATOR . '.symlinks-probe-link');
    }

    /**
     * Expired and invalidated entries stay on disk until they are pruned, so the driver the
     * nightly task runs against has to be pruneable.
     */
    public function testTheFilesystemPoolCanBePruned(): void
    {
        self::assertInstanceOf(PruneableInterface::class, $this->factory(CacheDriver::Filesystem)->createPool());
    }

    public function testThePoolIsBuiltOnce(): void
    {
        $factory = $this->factory(CacheDriver::Memory);

        self::assertSame($factory->createPool(), $factory->createPool());
    }

    public function testAnUnavailableDriverIsReportedWithWhatIsMissing(): void
    {
        if (extension_loaded('redis') || class_exists(\Predis\Client::class)) {
            self::markTestSkipped('The server has redis support, so the driver is available.');
        }

        $this->expectException(UnsupportedCacheDriverException::class);
        $this->expectExceptionMessage('redis extension');

        $this->factory(CacheDriver::Redis)->createPool();
    }

    private function factory(CacheDriver $driver, TagsStorage $tagsStorage = TagsStorage::Auto): CachePoolFactory
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'johncms-cache-test-' . bin2hex(random_bytes(6));

        return new CachePoolFactory(new CacheSettings(
            driver: $driver,
            namespace: 'tests',
            directory: $this->directory,
            tagsStorage: $tagsStorage,
        ));
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_link($path) || is_file($path)) {
                unlink($path);
                continue;
            }

            $this->removeDirectory($path);
        }

        rmdir($directory);
    }
}
