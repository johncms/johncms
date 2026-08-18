<?php

declare(strict_types=1);

namespace Johncms\Cache;

use ValueError;

/**
 * Reads the cache settings out of config/autoload/cache.*.php.
 *
 * A factory rather than constructor arguments wired in services.php: the container is compiled
 * and cached, so anything resolved while building it would be frozen into the cache and a
 * changed configuration would keep being ignored until the cache is cleared.
 */
final class CacheSettingsFactory
{
    public function __invoke(): CacheSettings
    {
        $defaults = new CacheSettings();
        $directory = config('cache.directory');

        return new CacheSettings(
            driver: $this->driver($defaults->driver),
            namespace: (string) config('cache.namespace', $this->defaultNamespace()),
            defaultLifetime: (int) config('cache.default_lifetime', $defaults->defaultLifetime),
            directory: $directory === null ? null : (string) $directory,
            tagsStorage: $this->tagsStorage($defaults->tagsStorage),
            redisDsn: (string) config('cache.redis.dsn', $defaults->redisDsn),
        );
    }

    /**
     * The version is part of the namespace: an upgrade that changes what is kept in the cache
     * then starts from an empty one instead of reading entries written by the previous release.
     */
    private function defaultNamespace(): string
    {
        return 'jc' . str_replace('.', '_', CMS_VERSION);
    }

    private function driver(CacheDriver $default): CacheDriver
    {
        $configured = config('cache.driver');

        if ($configured === null) {
            return $default;
        }

        try {
            return CacheDriver::from((string) $configured);
        } catch (ValueError) {
            throw new UnsupportedCacheDriverException(
                sprintf(
                    'Unknown cache driver "%s" in config. Supported drivers: %s.',
                    (string) $configured,
                    implode(', ', array_column(CacheDriver::cases(), 'value'))
                )
            );
        }
    }

    private function tagsStorage(TagsStorage $default): TagsStorage
    {
        $configured = config('cache.tags_storage');

        if ($configured === null) {
            return $default;
        }

        try {
            return TagsStorage::from((string) $configured);
        } catch (ValueError) {
            throw new UnsupportedCacheDriverException(
                sprintf(
                    'Unknown cache tags storage "%s" in config. Supported values: %s.',
                    (string) $configured,
                    implode(', ', array_column(TagsStorage::cases(), 'value'))
                )
            );
        }
    }
}
