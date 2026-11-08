<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Closure;
use Johncms\Cache\CacheInterface;
use Johncms\Database\TransactionCallbacks;
use Johncms\System\i18n\Translator;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Keeps what has been read about the structure of collections: the collection itself, its fields
 * and its sections. None of it depends on who is looking, so it is shared by every visitor through
 * the cache, and a request keeps what it has read in memory on top of that — a page built of
 * several lists of one collection asks for the same structure many times.
 *
 * An entry is filed under the tag of its collection, which every committed change of the
 * collection drops (CollectionCacheInvalidator). Names of collections and fields are translated as
 * they are read, so an entry is kept per locale. Inside a transaction nothing is remembered: a
 * write must see what is actually stored.
 */
final class CollectionStructureMemo implements ResetInterface
{
    /** Tag of every entry, whichever collection it describes */
    private const string CACHE_TAG = 'collections_structure';

    /** @var array<string, mixed> */
    private array $values = [];

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly Translator $translator,
    ) {
    }

    /**
     * @template T
     * @param int|null $collectionId The collection the entry describes; null when it is not known
     *                               before the entry is read, and only forgetting the whole
     *                               structure drops the entry.
     * @param Closure(): T $load
     * @return T
     */
    public function remember(string $key, ?int $collectionId, Closure $load): mixed
    {
        if (TransactionCallbacks::hasActiveTransaction()) {
            return $load();
        }
        $key .= '_' . $this->translator->getLocale();
        if (! array_key_exists($key, $this->values)) {
            $tags = [self::CACHE_TAG, 'collections'];
            if ($collectionId !== null) {
                $tags[] = CollectionCacheInvalidator::tag($collectionId);
            }
            // The key may carry an owner like "johncms/news", which the cache does not accept.
            $this->values[$key] = $this->cache->rememberForever('collections_structure_' . hash('xxh128', $key), $load, $tags);
        }
        return $this->values[$key];
    }

    /** Forgets what this request has read; the shared entries stay. */
    public function reset(): void
    {
        $this->values = [];
    }

    /** Forgets the structure of every collection, for everyone. */
    public function invalidate(): void
    {
        $this->cache->invalidateTags(self::CACHE_TAG);
        $this->reset();
    }
}
