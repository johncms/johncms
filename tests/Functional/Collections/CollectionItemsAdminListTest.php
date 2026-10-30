<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The items of a collection in the panel: a live list narrowed by a search, a section with its
 * subtree and the status, with the fields the collection marks as its columns.
 */
final class CollectionItemsAdminListTest extends FunctionalTestCase
{
    private int $collection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collection = Fixture::insert('collections', [
            'code' => 'blog', 'name' => 'Blog', 'settings' => json_encode(['has_sections' => true, 'per_page' => 10]),
            'sort' => 100, 'active' => 1, 'public' => 1,
        ]);
    }

    public function testTheFieldsMarkedForTheListAreItsColumns(): void
    {
        $author = $this->field('author', 'Author', 'string', inList: true);
        $this->field('body', 'Body', 'text', inList: false);
        $item = $this->item('Hello', 'hello');
        Fixture::insert('collection_item_values', ['item_id' => $item, 'field_id' => $author, 'value_string' => 'Maria', 'sort' => 0]);

        $content = $this->page('');

        self::assertMatchesRegularExpression('#<th scope="col"[^>]*>Author</th>#', $content);
        self::assertDoesNotMatchRegularExpression('#<th scope="col"[^>]*>Body</th>#', $content);
        self::assertStringContainsString('Maria', $content);
    }

    public function testASectionNarrowsTheItemsToItsSubtree(): void
    {
        $tech = $this->section('Tech', 'tech');
        $php = $this->section('PHP', 'php', $tech);
        $life = $this->section('Life', 'life');
        $this->item('In PHP', 'in-php', $php);
        $this->item('In life', 'in-life', $life);
        $this->item('Outside', 'outside');

        $narrowed = $this->page('?section=' . $tech);
        $outside = $this->page('?section=0');

        self::assertStringContainsString('In PHP', $narrowed);
        self::assertStringContainsString('Tech › PHP', $narrowed);
        self::assertStringNotContainsString('In life', $narrowed);
        self::assertStringContainsString('Outside', $outside);
        self::assertStringNotContainsString('In PHP', $outside);
    }

    public function testTheSearchAndTheStatusNarrowTheItems(): void
    {
        $this->item('Laravel queues', 'queues');
        $this->item('Vite move', 'vite');
        $this->item('Hidden draft', 'draft', active: false);

        $found = $this->page('?q=queue');
        $hidden = $this->page('?status=hidden');

        self::assertStringContainsString('Laravel queues', $found);
        self::assertStringNotContainsString('Vite move', $found);
        self::assertStringContainsString('Hidden draft', $hidden);
        self::assertStringNotContainsString('Vite move', $hidden);
    }

    public function testTheLiveListGetsItsRegionsAlone(): void
    {
        $this->item('Live', 'live');

        $response = $this->handleRequest(
            '/admin/collections/' . $this->collection . '/items',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );
        $content = (string) $response->getContent();

        self::assertStringContainsString('data-live-list-region="results"', $content);
        self::assertStringNotContainsString('<html', $content);
    }

    public function testDeletingAnItemReturnsToThePageOfTheList(): void
    {
        $item = $this->item('Doomed', 'doomed');
        $back = '/admin/collections/' . $this->collection . '/items?status=published';

        $response = $this->handleRequest(
            '/admin/collections/' . $this->collection . '/items/' . $item . '/delete',
            'POST',
            $this->withCsrfToken(['back' => $back]),
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );
        $elsewhere = $this->handleRequest(
            '/admin/collections/' . $this->collection . '/items/' . $this->item('Kept', 'kept') . '/delete',
            'POST',
            $this->withCsrfToken(['back' => 'https://example.com/']),
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertSame($back, $response->headers->get('Location'));
        self::assertNull(ContentCollectionItem::query()->find($item));
        self::assertSame('/admin/collections/' . $this->collection . '/items', $elsewhere->headers->get('Location'));
    }

    public function testAFieldIsMarkedForTheListInItsForm(): void
    {
        $response = $this->handleRequest(
            '/admin/collections/' . $this->collection . '/fields',
            'POST',
            $this->withCsrfToken(['name' => 'Price', 'code' => 'price', 'type' => 'double', 'sort' => '100', 'in_list' => '1']),
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertTrue(ContentCollectionField::query()->where('code', 'price')->value('in_list'));
    }

    /**
     * The regions of the list alone: the whole page carries the query log of the process when
     * debugging is on, and the names of the fixtures with it.
     */
    private function page(string $query): string
    {
        $response = $this->handleRequest(
            '/admin/collections/' . $this->collection . '/items' . $query,
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $query);

        return (string) $response->getContent();
    }

    private function field(string $code, string $name, string $type, bool $inList): int
    {
        return Fixture::insert('collection_fields', [
            'collection_id' => $this->collection, 'code' => $code, 'name' => $name, 'type' => $type,
            'required' => 0, 'multiple' => 0, 'sort' => 100, 'in_list' => $inList ? 1 : 0,
        ]);
    }

    private function section(string $name, string $code, ?int $parent = null): int
    {
        return Fixture::insert('collection_sections', [
            'collection_id' => $this->collection, 'parent' => $parent, 'name' => $name, 'code' => $code, 'active' => 1, 'sort' => 100,
        ]);
    }

    private function item(string $name, string $code, ?int $section = null, bool $active = true): int
    {
        return Fixture::insert('collection_items', [
            'collection_id' => $this->collection, 'section_id' => $section, 'name' => $name, 'code' => $code,
            'active' => $active ? 1 : 0, 'sort' => 100, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
