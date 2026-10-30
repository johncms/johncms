<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The forms of a collection in the panel: an item with its publishing dates and fields, and a
 * section that says where it stands.
 */
final class CollectionFormsAdminTest extends FunctionalTestCase
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

    public function testAnItemTakesTheDatesOfItsPickers(): void
    {
        $response = $this->save(['name' => 'Dated', 'code' => 'dated', 'active' => '1', 'active_from' => '2026-11-01T09:30', 'active_to' => '']);

        $item = ContentCollectionItem::query()->where('code', 'dated')->firstOrFail();
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('2026-11-01 09:30', $item->active_from?->format('Y-m-d H:i'));
        self::assertNull($item->active_to);
    }

    public function testARequiredFieldLeftEmptyStopsTheItem(): void
    {
        Fixture::insert('collection_fields', [
            'collection_id' => $this->collection, 'code' => 'author', 'name' => 'Author', 'type' => 'string',
            'required' => 1, 'multiple' => 0, 'sort' => 100,
        ]);

        $response = $this->save(['name' => 'Anonymous', 'code' => 'anonymous', 'field_author' => '  ']);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Заполните поле «Author»', (string) $response->getContent());
        self::assertFalse(ContentCollectionItem::query()->where('code', 'anonymous')->exists());
    }

    public function testTheFormOfASectionSaysWhereItStands(): void
    {
        $tech = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'parent' => null, 'name' => 'Tech', 'code' => 'tech', 'active' => 1, 'sort' => 100]);
        $php = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'parent' => $tech, 'name' => 'PHP', 'code' => 'php', 'active' => 1, 'sort' => 100]);

        $response = $this->handleRequest(
            '/admin/collections/' . $this->collection . '/sections/new?parent=' . $php,
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );
        $content = (string) $response->getContent();

        self::assertStringContainsString('<h1 class="admin-page__title">Новый раздел</h1>', $content);
        self::assertStringContainsString('Tech › PHP', $content);
    }

    /**
     * @param array<string, string> $fields
     */
    private function save(array $fields): Response
    {
        return $this->handleRequest(
            '/admin/collections/' . $this->collection . '/items',
            'POST',
            $this->withCsrfToken($fields + ['sort' => '100']),
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );
    }
}
