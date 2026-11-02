<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsPublicApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\Services\CollectionSearchIndexer;
use Johncms\Modules\Collections\Domain\Enums\FilterOperator;
use Johncms\Modules\Collections\Domain\Query\CollectionTextSearchDTO;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Query\FieldFilterDTO;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionSearchTest extends FunctionalTestCase
{
    private int $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = Fixture::insert('collections', ['code' => 'search', 'name' => 'Search', 'active' => 1, 'public' => 0, 'settings' => json_encode(['has_sections' => true, '_content_permission' => 'news.manage', 'search_fields' => ['extra']])]);
        Fixture::insert('collection_fields', ['collection_id' => $this->collection, 'code' => 'labels', 'name' => 'Labels', 'type' => 'string', 'multiple' => 1, 'role' => 'tags']);
        Fixture::insert('collection_fields', ['collection_id' => $this->collection, 'code' => 'extra', 'name' => 'Extra', 'type' => 'html']);
    }

    public function testSearchUsesRenderedPlainTextAndExplicitCustomFieldsInApiAndAdmin(): void
    {
        $first = $this->item('First', '<p>Многострочный <b>текст</b> &amp; 100% проверка</p><script>scriptneedle</script>', ['extra' => '<p>customneedle</p>']);
        $this->item('Second', '<p>Other 100x</p>');
        $public = $this->container()->get(CollectionsPublicApiInterface::class);
        self::assertSame([$first], array_map(static fn ($item) => $item->id, $public->getItems($this->query('ТЕКСТ'))));
        self::assertSame(1, $public->countItems($this->query('100%')));
        self::assertSame(0, $public->countItems($this->query('scriptneedle')));
        self::assertSame(0, $public->countItems($this->query('customneedle')));
        self::assertSame(1, $public->getPage($this->query('customneedle', ['extra']))->total);
        self::assertSame(1, $public->countItems($this->query('Многострочный текст')));
        $user = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $response = $this->handleRequest('/admin/collections/' . $this->collection . '/items?q=customneedle', cookies: $this->actingAs($user));
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('First', (string) $response->getContent());
        self::assertStringNotContainsString('Second', (string) $response->getContent());
    }

    public function testTagsAreNormalizedDeduplicatedAndMatchedExactly(): void
    {
        $php = $this->item('PHP', 'Body', ['labels' => [' PHP ', 'php', 'Php', '  CMS   tools  ']]);
        $this->item('IDE', 'Body', ['labels' => ['phpstorm']]);
        $api = $this->container()->get(CollectionsApiInterface::class);
        self::assertSame(['php', 'cms tools'], $api->getItem($php)->values['labels']);
        $query = new ContentCollectionItemQuery($this->collection, filters: [new FieldFilterDTO('field.labels', FilterOperator::Eq, ' PHP ')]);
        $public = $this->container()->get(CollectionsPublicApiInterface::class);
        self::assertSame([$php], array_map(static fn ($item) => $item->id, $public->getItems($query)));
        self::assertSame(1, $public->countItems($query));
        self::assertSame(['php', 'phpstorm'], $public->suggestTags($this->collection, 'labels', ' PH '));
        $this->expectException(\InvalidArgumentException::class);
        $public->getItems(new ContentCollectionItemQuery($this->collection, filters: [new FieldFilterDTO('field.labels', FilterOperator::Like, '%php%')]));
    }

    public function testHiddenItemsCannotLeakThroughSearchCountsOrTagSuggestions(): void
    {
        $visible = $this->item('Visible', 'needle', ['labels' => ['visible-tag']]);
        $hidden = $this->item('Hidden', 'needle', ['labels' => ['hidden-tag']]);
        $future = $this->item('Future', 'needle', ['labels' => ['future-tag']]);
        $expired = $this->item('Expired', 'needle', ['labels' => ['expired-tag']]);
        $section = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'code' => 'hidden', 'name' => 'Hidden', 'active' => 0]);
        $underHidden = $this->item('Under hidden section', 'needle', ['labels' => ['section-tag']]);
        Capsule::table('collection_items')->where('id', $hidden)->update(['active' => false]);
        Capsule::table('collection_items')->where('id', $future)->update(['active_from' => '2099-01-01 00:00:00']);
        Capsule::table('collection_items')->where('id', $expired)->update(['active_to' => '2000-01-01 00:00:00']);
        Capsule::table('collection_items')->where('id', $underHidden)->update(['section_id' => $section]);
        $public = $this->container()->get(CollectionsPublicApiInterface::class);
        $query = $this->query('needle');
        self::assertSame([$visible], array_map(static fn ($item) => $item->id, $public->getItems($query)));
        self::assertSame(1, $public->countItems($query));
        self::assertSame(1, $public->getPage($query)->total);
        self::assertSame(['visible-tag'], $public->suggestTags($this->collection, 'labels'));
        Capsule::table('collections')->where('id', $this->collection)->update(['active' => false]);
        self::assertSame(0, $public->countItems($query));
        self::assertSame([], $public->suggestTags($this->collection, 'labels'));
    }

    public function testAdminSuggestionsAreScopedByPermissionAndTextareaRemainsAFallback(): void
    {
        $this->item('Draft', 'Body', ['labels' => ['draft-tag']], active: false);
        $user = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $base = '/admin/collections/' . $this->collection;
        $response = $this->handleRequest($base . '/tags/labels?q=dra', cookies: $this->actingAs($user));
        self::assertSame(['draft-tag'], json_decode((string) $response->getContent(), true)['tags']);
        self::assertSame(403, $this->handleRequest($base . '/tags/labels', cookies: $this->actingAs(FunctionalUserFactory::create()))->getStatusCode());
        $foreign = Fixture::insert('collections', ['code' => 'private-tags', 'name' => 'Private']);
        self::assertSame(403, $this->handleRequest('/admin/collections/' . $foreign . '/tags/labels', cookies: $this->actingAs($user))->getStatusCode());
        $html = (string) $this->handleRequest($base . '/items/new', cookies: $this->actingAs($user))->getContent();
        self::assertStringContainsString('data-collection-tags', $html);
        self::assertMatchesRegularExpression('/<textarea[^>]*name="field_labels"/', $html);
    }

    public function testUnknownSearchFieldIsRejectedAndRebuildIsIdempotent(): void
    {
        $this->item('First', 'Body needle');
        $index = $this->container()->get(CollectionSearchIndexer::class);
        $index->rebuildCollection($this->collection);
        $count = Capsule::table('collection_search_documents')->count();
        $index->rebuildCollection($this->collection);
        self::assertSame($count, Capsule::table('collection_search_documents')->count());
        $this->expectException(\InvalidArgumentException::class);
        $this->container()->get(CollectionsPublicApiInterface::class)->getPage($this->query('needle', ['missing']));
    }

    private function query(string $term, array $fields = []): ContentCollectionItemQuery
    {
        return new ContentCollectionItemQuery($this->collection, onlyActive: false, search: new CollectionTextSearchDTO($term, $fields));
    }

    private function item(string $name, string $text, array $values = [], bool $active = true): int
    {
        $id = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'name' => $name, 'code' => 'entry-' . bin2hex(random_bytes(4)), 'active' => $active]);
        $this->container()->get(CollectionsApiInterface::class)->patchItem($id, new CollectionItemPatchDTO(['detailText' => $text], $values));
        // The functional harness keeps an outer transaction open; simulate an explicit rebuild.
        $this->container()->get(CollectionSearchIndexer::class)->rebuildItem($this->collection, $id);
        return $id;
    }
}
