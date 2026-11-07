<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Capsule\Manager;
use Johncms\Database\Migrations\PendingMigrations;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsPublicApiInterface;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Application\Services\CollectionsPermissions;
use Johncms\Modules\Collections\Application\Sitemap\CollectionsUrlsProvider;
use Johncms\Modules\Collections\Domain\Enums\FilterOperator;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Query\FieldFilterDTO;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionPublicationTest extends FunctionalTestCase
{
    private int $collection;
    private CollectionsPublicApiInterface $public;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = Fixture::insert('collections', ['code' => 'publication', 'name' => 'Publication', 'active' => 1, 'public' => 1]);
        $this->public = $this->container()->get(CollectionsPublicApiInterface::class);
        $this->container()->get(CollectionCodeCacheInterface::class)->invalidate();
    }

    protected function tearDown(): void
    {
        $this->container()->get(CollectionCodeCacheInterface::class)->invalidate();
        parent::tearDown();
    }

    #[DataProvider('hiddenCases')]
    public function testHiddenContentIsExcludedFromEveryPublicReadAndViewCounter(string $case): void
    {
        $top = $this->section('top');
        $child = $this->section('child', $top);
        $id = $this->item('entry', ['section_id' => $child]);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        match ($case) {
            'collection' => ContentCollection::query()->where('id', $this->collection)->update(['active' => false]),
            'item' => ContentCollectionItem::query()->where('id', $id)->update(['active' => false]),
            'section' => ContentCollectionSection::query()->where('id', $child)->update(['active' => false]),
            'ancestor' => ContentCollectionSection::query()->where('id', $top)->update(['active' => false]),
            'future' => ContentCollectionItem::query()->where('id', $id)->update(['active_from' => $now->modify('+1 day')->format('Y-m-d H:i:s')]),
            'expired' => ContentCollectionItem::query()->where('id', $id)->update(['active_to' => $now->modify('-1 day')->format('Y-m-d H:i:s')]),
            'foreign parent' => ContentCollectionSection::query()->where('id', $top)->update(['parent' => $this->foreignSection()]),
            'cycle' => ContentCollectionSection::query()->where('id', $top)->update(['parent' => $child]),
            default => throw new LogicException('Unknown publication test case.'),
        };
        $query = new ContentCollectionItemQuery(collectionId: $this->collection, onlyActive: false);
        self::assertSame([], $this->public->getItems($query));
        self::assertSame(0, $this->public->countItems($query));
        self::assertSame(0, $this->public->getPage($query)->total);
        self::assertSame([], $this->public->getSectionStats($this->collection));
        self::assertNull($this->public->findItem($this->collection, $id));
        self::assertNull($this->public->findItemByCode($this->collection, $child, 'entry'));
        self::assertNotNull($this->container()->get(CollectionsApiInterface::class)->getItem($id));
        try {
            $this->public->incrementViews($id);
            self::fail('Hidden items must not count public views.');
        } catch (CollectionItemFormException) {
            self::assertSame(17, ContentCollectionItem::query()->find($id)->view_count);
        }
        $urls = array_map(static fn ($entry) => $entry->loc, iterator_to_array($this->container()->get(CollectionsUrlsProvider::class)->getEntries('https://example.test')));
        foreach ($urls as $url) {
            self::assertStringNotContainsString('entry.html', $url);
        }
        self::assertSame(404, $this->handleRequest('/publication/top/child/entry.html')->getStatusCode());
        $listing = $this->handleRequest('/publication');
        self::assertStringNotContainsString('entry.html', (string) $listing->getContent());
    }

    public static function hiddenCases(): iterable
    {
        foreach (['collection', 'item', 'section', 'ancestor', 'future', 'expired', 'cycle', 'foreign parent'] as $case) {
            yield $case => [$case];
        }
    }

    public function testPrivateCollectionRemainsAvailableToModulePublicApiButHasNoOwnUrls(): void
    {
        $this->container()->get(CollectionCodeCacheInterface::class)->map();
        ContentCollection::query()->where('id', $this->collection)->update(['public' => false]);
        $id = $this->item('entry');
        self::assertNotNull($this->public->findItem($this->collection, $id));
        self::assertSame(1, $this->public->getPage(new ContentCollectionItemQuery($this->collection))->total);
        self::assertSame(404, $this->handleRequest('/publication/entry.html')->getStatusCode());
        self::assertSame([], iterator_to_array($this->container()->get(CollectionsUrlsProvider::class)->getEntries('https://example.test')));
        $this->public->incrementViews($id);
        self::assertSame(18, ContentCollectionItem::query()->find($id)->view_count);
    }

    public function testPendingMigrationsAnswerNotFoundWithoutAskingTheSchema(): void
    {
        $pending = $this->container()->get(PendingMigrations::class);
        Manager::table('migrations')->where('source', 'collections')->orderBy('id')->limit(1)->delete();
        $pending->forget();
        $connection = Manager::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        try {
            self::assertSame(404, $this->handleRequest('/no-such-page')->getStatusCode());
            $collections = array_filter($connection->getQueryLog(), static fn (array $entry): bool => str_contains($entry['query'], '"collections"') || str_contains($entry['query'], '`collections`'));
            self::assertSame([], array_values($collections));
        } finally {
            $connection->disableQueryLog();
            $pending->forget();
        }
    }

    public function testSectionStatsCountTheSubtreeAndItsLatestPublication(): void
    {
        $top = $this->section('top');
        $child = $this->section('child', $top);
        $empty = $this->section('empty');
        $this->item('old', ['section_id' => $top, 'active_from' => '2020-01-01 10:00:00']);
        $this->item('new', ['section_id' => $child, 'active_from' => '2024-05-06 07:08:09']);
        $this->item('hidden', ['section_id' => $child, 'active' => false, 'active_from' => '2025-01-01 00:00:00']);
        $this->item('loose');

        $stats = $this->public->getSectionStats($this->collection);

        self::assertSame([$top, $child], array_keys($stats), 'A section without published items is left out.');
        self::assertArrayNotHasKey($empty, $stats);
        self::assertSame(2, $stats[$top]->items);
        self::assertSame('2024-05-06 07:08:09', $stats[$top]->lastPublishedAt?->format('Y-m-d H:i:s'));
        self::assertSame(1, $stats[$child]->items);
    }

    public function testVisibleItemIsAvailableInListDetailAndSitemap(): void
    {
        $id = $this->item('entry');
        self::assertNotNull($this->public->findItem($this->collection, $id));
        self::assertNotNull($this->public->findItemByCode($this->collection, null, 'entry'));
        self::assertSame(200, $this->handleRequest('/publication/entry.html')->getStatusCode());
        self::assertStringContainsString('entry.html', (string) $this->handleRequest('/publication')->getContent());
        $urls = array_map(static fn ($entry) => $entry->loc, iterator_to_array($this->container()->get(CollectionsUrlsProvider::class)->getEntries('https://example.test')));
        self::assertContains('https://example.test/publication/entry.html', $urls);
    }

    #[DataProvider('windows')]
    public function testWindowBoundariesUseOneUtcMomentForCountAndPage(?int $from, ?int $to, bool $visible): void
    {
        $at = new DateTimeImmutable('2020-02-03T12:00:00+03:00');
        $this->item('entry', [
            'active_from' => $from === null ? null : $at->setTimezone(new DateTimeZone('UTC'))->modify(sprintf('%+d seconds', $from))->format('Y-m-d H:i:s'),
            'active_to' => $to === null ? null : $at->setTimezone(new DateTimeZone('UTC'))->modify(sprintf('%+d seconds', $to))->format('Y-m-d H:i:s'),
        ]);
        $query = new ContentCollectionItemQuery(collectionId: $this->collection, at: $at);
        $page = $this->public->getPage($query);
        self::assertSame($visible ? 1 : 0, $page->total);
        self::assertCount($visible ? 1 : 0, $page->items);
    }

    public static function windows(): iterable
    {
        yield 'both exact' => [0, 0, true];
        yield 'one second early' => [1, null, false];
        yield 'one second late' => [null, -1, false];
        yield 'inside' => [-1, 1, true];
        yield 'unbounded' => [null, null, true];
    }

    public function testPublicationDateFallbackAndTieBreakMatchDtoFilterAndOrdering(): void
    {
        $first = $this->item('fallback', ['created_at' => '2003-01-01 00:00:00']);
        $second = $this->item('explicit', ['created_at' => '2001-01-01 00:00:00', 'active_from' => '2003-01-01 00:00:00']);
        $this->item('older', ['created_at' => '2002-01-01 00:00:00']);
        $items = $this->public->getItems(new ContentCollectionItemQuery($this->collection));
        self::assertSame([$second, $first], [$items[0]->id, $items[1]->id]);
        self::assertSame('2003-01-01 00:00:00', $items[1]->publishedAt->format('Y-m-d H:i:s'));
        self::assertCount(2, $this->public->getItems(new ContentCollectionItemQuery(collectionId: $this->collection, filters: [
            new FieldFilterDTO('item.published_at', FilterOperator::Eq, new DateTimeImmutable('2003-01-01T00:00:00Z')),
        ])));
        $html = (string) $this->handleRequest('/publication')->getContent();
        self::assertLessThan(strpos($html, 'fallback.html'), strpos($html, 'explicit.html'));
    }

    public function testPreviewIsPermissionProtectedAndDoesNotPublishOrCountViews(): void
    {
        ContentCollection::query()->where('id', $this->collection)->update(['active' => false, 'public' => false]);
        $id = $this->item('draft', ['active' => false, 'detail_text' => '<p>Draft body</p><script>alert(1)</script>']);
        $url = '/admin/collections/' . $this->collection . '/items/' . $id . '/preview';
        self::assertContains($this->handleRequest($url)->getStatusCode(), [302, 403]);
        self::assertSame(403, $this->handleRequest($url, cookies: $this->actingAs(FunctionalUserFactory::create()))->getStatusCode());
        $editor = FunctionalUserFactory::createWithPermissions([CollectionsPermissions::MANAGE]);
        $response = $this->handleRequest($url, cookies: $this->actingAs($editor));
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Draft body', (string) $response->getContent());
        self::assertStringNotContainsString('<script>alert(1)</script>', (string) $response->getContent());
        self::assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        self::assertSame('noindex, nofollow', $response->headers->get('X-Robots-Tag'));
        $item = ContentCollectionItem::query()->find($id);
        self::assertFalse($item->active);
        self::assertSame(17, $item->view_count);
        self::assertSame('2001-01-01 00:00:00', $item->getRawOriginal('updated_at'));
        self::assertSame(404, $this->handleRequest('/admin/collections/' . ($this->collection + 1) . '/items/' . $id . '/preview', cookies: $this->actingAs($editor))->getStatusCode());
    }

    public function testMultipleValuesHaveTheSameOrderInPublicListAndCard(): void
    {
        $id = $this->item('entry');
        $field = Fixture::insert('collection_fields', ['collection_id' => $this->collection, 'code' => 'tags', 'name' => 'Tags', 'type' => 'string', 'multiple' => 1]);
        Fixture::insert('collection_item_values', ['item_id' => $id, 'field_id' => $field, 'value_string' => 'Later', 'sort' => 10]);
        Fixture::insert('collection_item_values', ['item_id' => $id, 'field_id' => $field, 'value_string' => 'First', 'sort' => 0]);
        self::assertSame(['First', 'Later'], $this->public->findItem($this->collection, $id)->values['tags']);
        self::assertSame(['First', 'Later'], $this->public->findItemByCode($this->collection, null, 'entry')->values['tags']);
        self::assertSame(['First', 'Later'], $this->public->getItems(new ContentCollectionItemQuery($this->collection))[0]->values['tags']);
    }

    public function testCachedCodeMapDoesNotDelayPublicationWindowTransitions(): void
    {
        $this->container()->get(CollectionCodeCacheInterface::class)->map();
        $this->item('timed', ['active_from' => '2030-01-01 00:00:00', 'active_to' => '2030-01-01 00:00:01']);
        foreach (['2029-12-31T23:59:59Z' => 0, '2030-01-01T00:00:00Z' => 1, '2030-01-01T00:00:02Z' => 0] as $moment => $expected) {
            self::assertSame($expected, $this->public->countItems(new ContentCollectionItemQuery(collectionId: $this->collection, at: new DateTimeImmutable($moment))));
        }
    }

    private function foreignSection(): int
    {
        $other = Fixture::insert('collections', ['code' => 'foreign', 'name' => 'Foreign', 'active' => 1, 'public' => 0]);
        return Fixture::insert('collection_sections', ['collection_id' => $other, 'code' => 'foreign', 'name' => 'Foreign', 'active' => 1]);
    }

    private function section(string $code, ?int $parent = null): int
    {
        return Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'parent' => $parent, 'code' => $code, 'name' => $code, 'active' => 1]);
    }

    private function item(string $code, array $attributes = []): int
    {
        return Fixture::insert('collection_items', $attributes + ['collection_id' => $this->collection, 'code' => $code, 'name' => $code, 'active' => true, 'view_count' => 17, 'created_at' => '2001-01-01 00:00:00', 'updated_at' => '2001-01-01 00:00:00']);
    }
}
