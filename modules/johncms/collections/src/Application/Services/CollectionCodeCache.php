<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Cache\CacheInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

/**
 * Caches the map of publicly reachable collection code => id for the public URL resolver.
 *
 * The resolver runs as the low-priority catch-all, so it is also the 404 path
 * for every unmatched URL; caching the map keeps those misses off the database.
 */
final readonly class CollectionCodeCache implements CollectionCodeCacheInterface
{
    private const CACHE_KEY = 'collections_code_map';

    /** Tag the cached map is filed under */
    private const CACHE_TAG = 'collections';

    public function __construct(
        private CacheInterface $cache,
        private ContentCollectionRepositoryInterface $repository,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function map(): array
    {
        return $this->cache->rememberForever(
            self::CACHE_KEY,
            fn (): array => $this->repository->getPublicCodeMap(),
            [self::CACHE_TAG]
        );
    }

    public function invalidate(): void
    {
        $this->cache->invalidateTags(self::CACHE_TAG);
    }
}
