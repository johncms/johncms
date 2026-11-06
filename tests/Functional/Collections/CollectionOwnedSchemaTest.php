<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Application\Sitemap\CollectionsUrlsProvider;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Updates\SystemOperationContext;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionOwnedSchemaTest extends FunctionalTestCase
{
    private int $collection;
    private int $field;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = Fixture::insert('collections', [
            'code' => 'owned-news', 'name' => 'News', 'owner_module' => 'johncms/news',
            'declaration_key' => 'articles', 'label_domain' => 'news', 'active' => 1, 'public' => 1,
        ]);
        $this->field = Fixture::insert('collection_fields', [
            'collection_id' => $this->collection, 'code' => 'meta_title', 'name' => 'Meta title', 'type' => 'string',
            'owner_module' => 'johncms/news', 'label_domain' => 'news', 'role' => 'meta_title',
        ]);
    }

    public function testOwnerFieldsAreReadOnlyInUiAndProtectedAgainstForgedPosts(): void
    {
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $this->collection . '/fields';
        $response = $this->handleRequest($base . '/' . $this->field . '/edit', cookies: $this->actingAs($user));
        self::assertSame(200, $response->getStatusCode());
        self::assertMatchesRegularExpression('/name="code"[^>]* disabled/', (string) $response->getContent());
        $response = $this->handleRequest($base, cookies: $this->actingAs($user));
        self::assertStringNotContainsString($base . '/' . $this->field . '/delete', (string) $response->getContent());
        $this->postForm($base, ['id' => $this->field, 'name' => 'Forged', 'code' => 'forged', 'type' => 'integer', 'sort' => '100'], $user);
        self::assertSame('meta_title', Capsule::table('collection_fields')->where('id', $this->field)->value('code'));
        $this->postForm($base . '/' . $this->field . '/delete', [], $user);
        self::assertTrue(Capsule::table('collection_fields')->where('id', $this->field)->exists());
        $response = $this->postForm($base, ['name' => 'Custom', 'code' => 'custom', 'type' => 'string', 'sort' => '100'], $user);
        self::assertSame(302, $response->getStatusCode());
        self::assertNull(Capsule::table('collection_fields')->where('code', 'custom')->value('owner_module'));
    }

    public function testTrustedHookCanPrepareTheDeclaredTypeThroughSchemaApi(): void
    {
        $schema = $this->container()->get(CollectionSchemaApiInterface::class);
        $context = $this->container()->get(SystemOperationContext::class);
        $context->runTrusted(fn () => $schema->saveField($this->field, new CollectionFieldFormDTO(
            collectionId: $this->collection, code: 'meta_title', name: 'Meta title', type: FieldType::Integer, required: false, multiple: false, sort: 100, role: 'meta_title'
        )));
        self::assertSame(FieldType::Integer, $schema->getFields($this->collection)[0]->type);
    }

    public function testOwnedCollectionNeverMintsDuplicatePublicPagesAndStillOpensInAdmin(): void
    {
        Fixture::insert('collection_items', ['collection_id' => $this->collection, 'code' => 'entry', 'name' => 'Entry', 'active' => 1]);
        $this->container()->get(CollectionCodeCacheInterface::class)->invalidate();
        self::assertSame(404, $this->handleRequest('/owned-news')->getStatusCode());
        self::assertSame(404, $this->handleRequest('/owned-news/entry.html')->getStatusCode());
        foreach ($this->container()->get(CollectionsUrlsProvider::class)->getEntries('https://example.test') as $entry) {
            self::assertStringNotContainsString('/owned-news', $entry->loc);
        }
        self::assertSame(200, $this->handleRequest('/admin/collections/' . $this->collection . '/items', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getStatusCode());
        self::assertSame($this->collection, $this->container()->get(CollectionsApiInterface::class)->findDeclaredCollection('johncms/news', 'articles')->id);
    }

    public function testCollectionIdentityCannotBeChangedByPostingAnotherCode(): void
    {
        $response = $this->postForm('/admin/collections', [
            'id' => $this->collection, 'code' => 'forged', 'name' => 'Forged', 'active' => '1', 'sort' => '100', 'per_page' => '20',
        ], FunctionalUserFactory::createSupervisor());
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('owned-news', Capsule::table('collections')->where('id', $this->collection)->value('code'));
    }
}
