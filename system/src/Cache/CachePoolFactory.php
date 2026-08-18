<?php

declare(strict_types=1);

namespace Johncms\Cache;

use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Builds the cache pool the CMS works through.
 *
 * Every driver comes out of here tag-aware, even the ones that are not tag-aware themselves:
 * tagging an item on a plain pool throws, and a module must not have to care which driver the
 * site is configured with.
 */
final class CachePoolFactory
{
    private null|(TagAwareAdapterInterface&TagAwareCacheInterface) $pool = null;

    public function __construct(
        private readonly CacheSettings $settings,
    ) {
    }

    public function create(): CacheInterface
    {
        return new SymfonyCache($this->createPool());
    }

    /**
     * The pool behind the cache. The console commands that clear or prune it need the pool
     * itself, which the CacheInterface contract deliberately does not expose.
     */
    public function createPool(): TagAwareAdapterInterface&TagAwareCacheInterface
    {
        return $this->pool ??= $this->build();
    }

    private function build(): TagAwareAdapterInterface&TagAwareCacheInterface
    {
        return match ($this->settings->driver) {
            CacheDriver::Filesystem => $this->buildFilesystemPool(),
            CacheDriver::Apcu       => new TagAwareAdapter($this->buildApcuAdapter()),
            CacheDriver::Redis      => $this->buildRedisPool(),
            CacheDriver::Memory     => new TagAwareAdapter(new ArrayAdapter($this->settings->defaultLifetime)),
            CacheDriver::Disabled   => new TagAwareAdapter(new NullAdapter()),
        };
    }

    /**
     * The tag-aware filesystem adapter relates tags to entries with symlinks, which some shared
     * hostings and Windows do not give us. Where they are missing, a plain adapter with a
     * separate pool for the tag versions does the same job without them.
     */
    private function buildFilesystemPool(): TagAwareAdapterInterface&TagAwareCacheInterface
    {
        $directory = $this->settings->directory ?? CACHE_PATH . 'app';

        if ($this->useSymlinkTags($directory)) {
            return new FilesystemTagAwareAdapter($this->settings->namespace, $this->settings->defaultLifetime, $directory);
        }

        return new TagAwareAdapter(
            new FilesystemAdapter($this->settings->namespace, $this->settings->defaultLifetime, $directory),
            new FilesystemAdapter($this->settings->namespace . '.tags', 0, $directory),
        );
    }

    private function buildApcuAdapter(): ApcuAdapter
    {
        if (! ApcuAdapter::isSupported()) {
            throw new UnsupportedCacheDriverException(
                'The apcu cache driver needs the APCu extension, which is not enabled on this server.'
            );
        }

        return new ApcuAdapter($this->settings->namespace, $this->settings->defaultLifetime);
    }

    private function buildRedisPool(): RedisTagAwareAdapter
    {
        if (! extension_loaded('redis') && ! class_exists(\Predis\Client::class)) {
            throw new UnsupportedCacheDriverException(
                'The redis cache driver needs either the redis extension or the predis/predis package.'
            );
        }

        return new RedisTagAwareAdapter(
            RedisAdapter::createConnection($this->settings->redisDsn),
            $this->settings->namespace,
            $this->settings->defaultLifetime
        );
    }

    private function useSymlinkTags(string $directory): bool
    {
        return match ($this->settings->tagsStorage) {
            TagsStorage::Symlink => true,
            TagsStorage::Files   => false,
            TagsStorage::Auto    => $this->symlinksSupported($directory),
        };
    }

    /**
     * Whether a symlink can be made inside the cache directory. The answer is written next to
     * the cache so the probe runs once per installation rather than once per request; clearing
     * the cache drops the marker and the probe runs again.
     */
    private function symlinksSupported(string $directory): bool
    {
        $marker = $directory . DIRECTORY_SEPARATOR . '.symlinks';

        if (is_file($marker)) {
            return file_get_contents($marker) === '1';
        }

        if (! is_dir($directory) && ! @mkdir($directory, 0o775, true) && ! is_dir($directory)) {
            return false;
        }

        $supported = $this->probeSymlink($directory);
        @file_put_contents($marker, $supported ? '1' : '0');

        return $supported;
    }

    private function probeSymlink(string $directory): bool
    {
        $target = $directory . DIRECTORY_SEPARATOR . '.symlinks-probe';
        $link = $directory . DIRECTORY_SEPARATOR . '.symlinks-probe-link';

        @unlink($link);

        if (@file_put_contents($target, '') === false) {
            return false;
        }

        $supported = @symlink($target, $link) && is_link($link);

        @unlink($link);
        @unlink($target);

        return $supported;
    }
}
