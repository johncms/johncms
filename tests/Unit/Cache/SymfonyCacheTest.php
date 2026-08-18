<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use Johncms\Cache\CacheInterface;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;
use Tests\Support\InMemoryCache;

final class SymfonyCacheTest extends TestCase
{
    public function testTheCallbackRunsOnceAndTheValueIsServedFromTheCacheAfterwards(): void
    {
        $cache = InMemoryCache::create();
        $calls = 0;
        $compute = function () use (&$calls): array {
            $calls++;

            return ['value' => $calls];
        };

        self::assertSame(['value' => 1], $cache->remember('key', 60, $compute));
        self::assertSame(['value' => 1], $cache->remember('key', 60, $compute));
        self::assertSame(1, $calls);
    }

    /**
     * What tags buy over deleting keys by hand: the caller invalidates a group without knowing
     * which keys are in it.
     */
    public function testEveryEntryUnderAnInvalidatedTagIsRecomputed(): void
    {
        $cache = InMemoryCache::create();
        $cache->rememberForever('first', fn (): string => 'a', ['news']);
        $cache->rememberForever('second', fn (): string => 'b', ['news']);

        $cache->invalidateTags('news');

        self::assertSame('a2', $cache->rememberForever('first', fn (): string => 'a2', ['news']));
        self::assertSame('b2', $cache->rememberForever('second', fn (): string => 'b2', ['news']));
    }

    public function testAnEntryUnderAnotherTagIsLeftAlone(): void
    {
        $cache = InMemoryCache::create();
        $cache->rememberForever('tagged', fn (): string => 'news', ['news']);
        $cache->rememberForever('other', fn (): string => 'counters', ['counters']);
        $cache->rememberForever('untagged', fn (): string => 'plain');

        $cache->invalidateTags('news');

        self::assertSame('counters', $cache->rememberForever('other', fn (): string => 'lost', ['counters']));
        self::assertSame('plain', $cache->rememberForever('untagged', fn (): string => 'lost'));
    }

    public function testAnEntryUnderAnyOfItsTagsIsInvalidated(): void
    {
        $cache = InMemoryCache::create();
        $cache->rememberForever('key', fn (): string => 'first', ['news', 'counters']);

        $cache->invalidateTags('counters');

        self::assertSame('second', $cache->rememberForever('key', fn (): string => 'second', ['news', 'counters']));
    }

    public function testThePsr16MethodsWorkOnTheSameEntries(): void
    {
        $cache = InMemoryCache::create();

        $cache->set('key', 'value', 60);
        self::assertTrue($cache->has('key'));
        self::assertSame('value', $cache->get('key'));

        $cache->delete('key');
        self::assertFalse($cache->has('key'));
        self::assertSame('fallback', $cache->get('key', 'fallback'));
    }

    public function testTheMultipleMethodsFallBackForWhatIsMissing(): void
    {
        $cache = InMemoryCache::create();
        $cache->setMultiple(['first' => 1, 'second' => 2], 60);

        $read = [];
        foreach ($cache->getMultiple(['first', 'second', 'third'], 'none') as $key => $value) {
            $read[$key] = $value;
        }

        self::assertSame(['first' => 1, 'second' => 2, 'third' => 'none'], $read);

        $cache->deleteMultiple(['first', 'second']);
        self::assertNull($cache->get('first'));
    }

    public function testClearingDropsEverythingTaggedOrNot(): void
    {
        $cache = InMemoryCache::create();
        $cache->rememberForever('tagged', fn (): string => 'a', ['news']);
        $cache->set('plain', 'b', 60);

        self::assertTrue($cache->clear());

        self::assertNull($cache->get('plain'));
        self::assertSame('recomputed', $cache->rememberForever('tagged', fn (): string => 'recomputed', ['news']));
    }

    /**
     * The reserved characters of PSR-16 are rejected rather than quietly mangled into a key that
     * collides with another one.
     *
     * symfony/cache validates the key inside an assert(), so the rejection only happens where
     * assertions are executed. With them off — how a site runs in production — an invalid key
     * passes through untouched, and there is nothing here to assert.
     */
    public function testAKeyWithReservedCharactersIsRejected(): void
    {
        if (ini_get('zend.assertions') !== '1') {
            self::markTestSkipped('The key validation of symfony/cache needs zend.assertions=1.');
        }

        $cache = InMemoryCache::create();

        $this->expectException(InvalidArgumentException::class);

        $cache->get('news{1}');
    }

    public function testTheCacheIsUsableThroughThePsr16Contract(): void
    {
        self::assertInstanceOf(\Psr\SimpleCache\CacheInterface::class, InMemoryCache::create());
        self::assertInstanceOf(CacheInterface::class, InMemoryCache::create());
    }
}
