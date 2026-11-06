<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationsSynchronizer;
use Johncms\Modules\Collections\Application\Services\CollectionOwnerUrls;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\ModuleDependencyGraph;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\News\Application\Collections\NewsCollectionDeclaration;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class NewsCollectionDeclarationTest extends FunctionalTestCase
{
    public function testSynchronizationCreatesTheNewsSchemaIdempotentlyWithoutMovingLegacyData(): void
    {
        $legacy = Fixture::insert('news_articles', ['name' => 'Legacy article', 'code' => 'legacy-article', 'active' => 1, 'text' => '<p>Legacy body</p>', 'created_at' => date('Y-m-d H:i:s'), 'created_by' => FunctionalUserFactory::create()->id]);
        $sync = $this->container()->get(CollectionDeclarationsSynchronizer::class);
        $sync->synchronize();
        $sync->validate();
        $api = $this->container()->get(CollectionsApiInterface::class);
        $collection = $api->findDeclaredCollection(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY);
        self::assertNotNull($collection);
        self::assertFalse($collection->public);
        self::assertSame('news.manage', $collection->settings['_content_permission']);
        $sync->synchronize();
        self::assertSame($collection->id, $api->findDeclaredCollection(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY)->id);
        self::assertSame(4, Capsule::table('collection_fields')->where('collection_id', $collection->id)->count());
        self::assertSame(0, Capsule::table('collection_items')->where('collection_id', $collection->id)->count());
        $fields = $this->container()->get(CollectionSchemaApiInterface::class)->getFields($collection->id);
        self::assertSame(['meta_title', 'meta_description', 'meta_keywords', 'tags'], array_map(static fn ($field) => $field->role, $fields));
        self::assertSame(FieldType::Text, $fields[2]->type);
        self::assertTrue($fields[3]->multiple);
        self::assertSame('Legacy article', Capsule::table('news_articles')->where('id', $legacy)->value('name'));
        self::assertSame(404, $this->handleRequest('/news/legacy-article.html')->getStatusCode());
        self::assertSame(404, $this->handleRequest('/admin/news', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getStatusCode());
    }

    public function testOwnerUrlsKeepTheNewsShapeAndEditorCanUseTheCollectionWorkspace(): void
    {
        $this->container()->get(CollectionDeclarationsSynchronizer::class)->synchronize();
        $api = $this->container()->get(CollectionsApiInterface::class);
        $collection = $api->findDeclaredCollection(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY);
        $sections = $this->container()->get(CollectionSectionsApiInterface::class);
        $top = $sections->addSection(new CollectionSectionFormDTO($collection->id, null, 'top', 'Top', null, true, 100));
        $child = $sections->addSection(new CollectionSectionFormDTO($collection->id, $top, 'child', 'Child', null, true, 100));
        $id = $api->addItem(new CollectionItemFormDTO($collection->id, $child, 'entry', 'Entry', true, null, null, 100, null, null, ['tags' => [' PHP ']]));
        $rootId = $api->addItem(new CollectionItemFormDTO($collection->id, null, 'root', 'Root', false, null, null, 100, null, null, []));
        $urls = $this->container()->get(CollectionOwnerUrls::class);
        self::assertSame('/news/', $urls->collection($collection));
        self::assertSame('/news/top/child/entry.html', $urls->item($collection, $api->getItem($id)));
        self::assertSame('/news/root.html', $urls->item($collection, $api->getItem($rootId)));
        self::assertSame('/news/top/child/', $urls->section($collection, $sections->findSection($collection->id, $child)));
        self::assertSame('/admin/news/settings', $urls->settings($collection));
        $user = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $response = $this->handleRequest('/admin/collections/' . $collection->id . '/items', cookies: $this->actingAs($user));
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('/admin/news/settings', (string) $response->getContent());
        self::assertMatchesRegularExpression('~href="/admin/collections/' . $collection->id . '/items"[^>]*aria-current="page"~s', (string) $response->getContent());
        self::assertSame(200, $this->handleRequest($urls->preview($collection, $api->getItem($rootId)), cookies: $this->actingAs($user))->getStatusCode());
    }

    public function testSeoHintsKeepSourceMessagesAndFollowTheEditorLanguage(): void
    {
        $sync = $this->container()->get(CollectionDeclarationsSynchronizer::class);
        $sync->synchronize();
        $collection = $this->container()->get(CollectionsApiInterface::class)->findDeclaredCollection(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY);
        $title = 'Left empty, the title is made of the template in the settings of the news.';
        $other = 'Left empty, they are made of the template in the settings of the news.';
        $schema = $this->container()->get(CollectionSchemaApiInterface::class);
        $schema->saveField(null, new CollectionFieldFormDTO(
            collectionId: $collection->id, code: 'custom', name: 'Custom', type: FieldType::String_, required: false, multiple: false, sort: 500, settings: ['hint' => $title]
        ));
        $sync->synchronize();
        $hints = ['page_title' => $title, 'description' => $other, 'keywords' => $other];
        $user = FunctionalUserFactory::createSupervisor();
        foreach (['ru', 'en'] as $locale) {
            $response = $this->handleRequest('/admin/collections/' . $collection->id . '/items/new?setlng=' . $locale, cookies: $this->actingAs($user));
            self::assertSame(200, $response->getStatusCode());
            $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
            foreach ($hints as $code => $message) {
                $expected = $locale === 'en' ? $message : ($code === 'page_title'
                    ? 'Если оставить пустым, заголовок составится по шаблону из настроек новостей.'
                    : 'Если оставить пустыми, они составятся по шаблону из настроек новостей.');
                self::assertStringContainsString($expected, $document->querySelector('#f_field_' . $code)->parentElement->textContent);
            }
            self::assertStringContainsString($title, $document->querySelector('#f_field_custom')->parentElement->textContent);
        }
        foreach ($schema->getFields($collection->id) as $field) {
            if (isset($hints[$field->code])) {
                self::assertSame($hints[$field->code], $field->settings['hint']);
            }
        }
    }

    public function testTheModuleGraphProtectsTheCollectionsDependency(): void
    {
        $modules = $this->container()->get(ModuleRegistry::class)->enabled();
        self::assertArrayHasKey('johncms/collections', $modules['johncms/news']->requires->modules);
        self::assertContains('johncms/news', (new ModuleDependencyGraph($modules))->dependentsOf('johncms/collections'));
    }
}
