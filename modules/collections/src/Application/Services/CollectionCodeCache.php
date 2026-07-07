<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Cache;
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

    public function __construct(
        private Cache $cache,
        private ContentCollectionRepositoryInterface $repository,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function map(): array
    {
        return $this->cache->rememberForever(self::CACHE_KEY, fn (): array => $this->repository->getPublicCodeMap());
    }

    public function invalidate(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }
}
