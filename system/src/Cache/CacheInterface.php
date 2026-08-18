<?php

declare(strict_types=1);

namespace Johncms\Cache;

use Closure;
use DateInterval;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;

/**
 * The cache contract of the CMS.
 *
 * PSR-16 is the base, so anything expecting a standard cache can be handed this one. On top of
 * it come the two things PSR-16 has no answer for: reading through to a callback in one call,
 * and invalidating a whole group of entries at once by tag.
 *
 * Keys and tags follow the PSR-16 charset: the reserved characters {}()/\@: are rejected.
 */
interface CacheInterface extends SimpleCacheInterface
{
    /**
     * Returns the cached value, computing and storing it on a miss.
     *
     * @param string                 $key      Cache key.
     * @param int|DateInterval|null  $ttl      Lifetime; null keeps the entry until it is
     *                                         invalidated by tag or the cache is cleared.
     * @param Closure                $callback Computes the value on a miss.
     * @param string[]               $tags     Tags the entry is filed under.
     */
    public function remember(string $key, int|DateInterval|null $ttl, Closure $callback, array $tags = []): mixed;

    /**
     * The same as remember() without an expiry: the entry lives until a tag invalidates it or
     * the cache is cleared.
     *
     * @param string[] $tags
     */
    public function rememberForever(string $key, Closure $callback, array $tags = []): mixed;

    /**
     * Invalidates every entry filed under any of the given tags.
     *
     * The entries are marked stale rather than erased — the disk space comes back with the next
     * prune run, not with this call.
     */
    public function invalidateTags(string ...$tags): bool;
}
