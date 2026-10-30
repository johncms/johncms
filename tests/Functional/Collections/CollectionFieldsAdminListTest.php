<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The fields of a collection in the panel: a table of what its items carry.
 */
final class CollectionFieldsAdminListTest extends FunctionalTestCase
{
    public function testEveryFieldShowsItsTypeAndProperties(): void
    {
        $collection = Fixture::insert('collections', [
            'code' => 'shop', 'name' => 'Shop', 'settings' => json_encode(['has_sections' => false, 'per_page' => 10]),
            'sort' => 100, 'active' => 1, 'public' => 1,
        ]);
        $price = Fixture::insert('collection_fields', [
            'collection_id' => $collection, 'code' => 'price', 'name' => 'Price', 'type' => 'double',
            'required' => 1, 'multiple' => 0, 'sort' => 100, 'in_list' => 1,
        ]);

        $response = $this->handleRequest('/admin/collections/' . $collection . '/fields', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        $content = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('href="/admin/collections/' . $collection . '/fields/' . $price . '/edit">Price</a>', $content);
        self::assertStringContainsString('Число (дробное)', $content);
        self::assertStringContainsString('Обязательное', $content);
        self::assertStringContainsString('В списке', $content);
        self::assertMatchesRegularExpression('#href="/admin/collections/' . $collection . '/fields/' . $price . '/delete"\s*data-admin-modal#', $content);
    }
}
