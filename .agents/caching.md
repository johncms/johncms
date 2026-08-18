# Caching

The cache of the CMS is `Johncms\Cache\CacheInterface`. Inject it — there is no facade and no
helper. It is PSR-16 (`Psr\SimpleCache\CacheInterface`) with three additions: `remember()`,
`rememberForever()` and `invalidateTags()`.

Behind it sits symfony/cache. Which storage the site uses is decided by
`config/autoload/cache.*.php`; code must not care.

## Reading through the cache

```php
use Johncms\Cache\CacheInterface;

final readonly class SectionTree
{
    private const CACHE_TAG = 'news';

    public function __construct(private CacheInterface $cache) {}

    public function all(): array
    {
        return $this->cache->remember('news_tree', 600, fn (): array => $this->load(), [self::CACHE_TAG]);
    }
}
```

* `remember($key, $ttl, $callback, $tags)` — the TTL is in seconds; `null` means the entry lives
  until a tag invalidates it or the cache is cleared.
* `rememberForever($key, $callback, $tags)` — the same without an expiry.
* Plain writes go through the PSR-16 `set()` / `delete()`. There is no `put()` and no `forget()`:
  one name per operation.

## Invalidating

Never enumerate keys to clear a group. Tag the entries when they are written and drop the tag:

```php
$this->cache->invalidateTags('news');
```

Rules:

* Tag by what the data **is**, not by where it is used: `news`, `counters`, `collections`.
* Keep the tag in a class constant next to the keys it covers, so a new key only has to be
  tagged rather than added to a clearing method.
* Invalidation is lazy. The entries become misses at once, but the space they take comes back
  with `cache:pool:prune`, not with the call.

## Keys and tags

Keys and tags follow the PSR-16 charset: `{}()/\@:` are rejected and throw
`Psr\SimpleCache\InvalidArgumentException`. Prefix a key with the module it belongs to
(`news_tree`, `collections_code_map`).

A key must never carry what a visitor typed — it can end up as a file name on disk and it shows
up in a cache listing. Hash it, the way `CacheLoginThrottle` does.

## What may be cached

Arrays, scalars and DTOs. **Never Eloquent models**: they serialize together with their
connection state and relations, and an entry written before an upgrade is read back into a
changed class. Map to an array or a DTO first.

## Drivers

`filesystem` (default), `apcu`, `redis`, `array` (tests), `null`. Every one of them comes out of
`CachePoolFactory` tag-aware, so tagging works whatever the site is configured with.

The filesystem driver relates tags to entries with symlinks where the hosting allows them and
with plain files where it does not — probed once and remembered in `data/cache/app/.symlinks`.
Force either with `cache.tags_storage`.

Redis needs an eviction policy of `noeviction` or `volatile-*`; on `allkeys-*` symfony refuses to
start, because evicting a tag entry would silently orphan everything filed under it.

## Tests

Use `Tests\Support\InMemoryCache::create()` — a real cache over memory. Do not mock
`CacheInterface`: a mock of `remember()` proves nothing about whether the entry was tagged.

```php
$cache = InMemoryCache::create();
$service = new SectionTree($cache);
```

`InMemoryCache::over($storage)` takes an `ArrayAdapter` the test keeps, for the rare assertion
about what actually landed in the cache.

## Console

* `cache:pool:clear [tags...]` — clears what the modules cached, or only the given tags.
* `cache:pool:prune` — frees the space of expired and invalidated entries. Runs nightly from the
  scheduler; an installation whose cron is not set up needs it run by hand.
* `cache:clear` — a different thing entirely: wipes all of `data/cache`, the compiled container,
  routes and templates included. That is the one to use after an upgrade.
