<?php

declare(strict_types=1);

namespace Johncms\Cache;

use Closure;
use DateInterval;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * The cache of the CMS on top of symfony/cache.
 *
 * Two views of one pool: the PSR-16 methods are delegated to the standard Psr16Cache wrapper,
 * while remember() and the tags go to the pool directly. They have to be separate — a PSR-16
 * set() has nowhere to carry tags, so tagging only exists on the item, inside the callback.
 */
final class SymfonyCache implements CacheInterface
{
    private readonly Psr16Cache $simpleCache;

    public function __construct(
        private readonly TagAwareAdapterInterface&TagAwareCacheInterface $pool,
    ) {
        $this->simpleCache = new Psr16Cache($this->pool);
    }

    public function remember(string $key, int|DateInterval|null $ttl, Closure $callback, array $tags = []): mixed
    {
        return $this->pool->get($key, static function (ItemInterface $item) use ($ttl, $callback, $tags): mixed {
            $item->expiresAfter($ttl);

            if ($tags !== []) {
                $item->tag($tags);
            }

            return $callback();
        });
    }

    public function rememberForever(string $key, Closure $callback, array $tags = []): mixed
    {
        return $this->remember($key, null, $callback, $tags);
    }

    public function invalidateTags(string ...$tags): bool
    {
        return $this->pool->invalidateTags($tags);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->simpleCache->get($key, $default);
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return $this->simpleCache->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->simpleCache->delete($key);
    }

    public function clear(): bool
    {
        return $this->simpleCache->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->simpleCache->getMultiple($keys, $default);
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        return $this->simpleCache->setMultiple($values, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->simpleCache->deleteMultiple($keys);
    }

    public function has(string $key): bool
    {
        return $this->simpleCache->has($key);
    }
}
