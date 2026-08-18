<?php

declare(strict_types=1);

namespace Johncms\Cache;

/**
 * What the cache pool is built from, resolved out of config once per process by
 * CacheSettingsFactory.
 *
 * A value object rather than config() calls inside the factory: a test wanting an in-memory
 * pool builds one instead of rewriting the configuration.
 */
final readonly class CacheSettings
{
    /**
     * @param CacheDriver $driver          The storage the pool is built on.
     * @param string      $namespace       Prefix isolating this installation inside the storage.
     *                                     Only -+_. and alphanumerics are allowed.
     * @param int         $defaultLifetime Lifetime of an entry that sets none, in seconds;
     *                                     0 keeps it until it is invalidated or cleared.
     * @param string|null $directory       Where the filesystem driver writes; null means
     *                                     data/cache/app.
     * @param TagsStorage $tagsStorage     How the filesystem driver relates tags to entries.
     * @param string      $redisDsn        Connection string of the redis driver.
     */
    public function __construct(
        public CacheDriver $driver = CacheDriver::Filesystem,
        public string $namespace = '',
        public int $defaultLifetime = 0,
        public ?string $directory = null,
        public TagsStorage $tagsStorage = TagsStorage::Auto,
        public string $redisDsn = 'redis://127.0.0.1:6379',
    ) {
    }
}
