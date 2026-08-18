<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Cache\CacheInterface;
use Johncms\Cache\SymfonyCache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * The cache of the CMS backed by memory, for tests that need a real one rather than a mock.
 *
 * Tag-aware like every configured driver is, so code under test may tag its entries and
 * invalidate them without the test caring which storage is behind it.
 */
final class InMemoryCache
{
    public static function create(): CacheInterface
    {
        return self::over(new ArrayAdapter());
    }

    /**
     * The same cache over a storage the test keeps a handle on — for the rare assertion about
     * what actually ended up in it, such as which keys were written.
     */
    public static function over(ArrayAdapter $storage): CacheInterface
    {
        return new SymfonyCache(new TagAwareAdapter($storage));
    }
}
