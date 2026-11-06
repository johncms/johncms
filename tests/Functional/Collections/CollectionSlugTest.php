<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Dom\HTMLDocument;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionSlugTest extends FunctionalTestCase
{
    public function testLivePreviewMatchesTheSavedAutomaticCodeForDifferentInputLanguages(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'slugs', 'name' => 'Slugs']);
        $user = FunctionalUserFactory::createSupervisor();
        $api = $this->container()->get(CollectionsApiInterface::class);
        foreach (['Привет, мир!', 'Українські новини', 'Crème brûlée', 'Ελληνικά νέα', 'مرحبا بالعالم'] as $name) {
            $response = $this->postForm('/admin/collections/' . $collection . '/slug', ['name' => $name], $user);
            self::assertSame(200, $response->getStatusCode());
            self::assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $data = json_decode(json: (string) $response->getContent(), associative: true, flags: JSON_THROW_ON_ERROR);
            self::assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $data['slug']);
            $id = $api->addItem(new CollectionItemFormDTO($collection, null, '', $name, false, null, null, 100, null, null, []));
            self::assertSame($api->getItem($id)->code, $data['slug']);
        }
        $response = $this->postForm('/admin/collections/slug', ['name' => str_repeat('А', 400)], $user);
        $data = json_decode(json: (string) $response->getContent(), associative: true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(240, strlen($data['slug']));
    }

    public function testSlugPreviewUsesSchemaOrCollectionContentAccessAndRequiresCsrf(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'editor-slugs', 'name' => 'Slugs', 'settings' => '{"_content_permission":"news.manage"}']);
        $editor = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $url = '/admin/collections/' . $collection . '/slug';
        self::assertSame(200, $this->postForm($url, ['name' => 'Привет'], $editor)->getStatusCode());
        self::assertSame(403, $this->postForm('/admin/collections/slug', ['name' => 'Привет'], $editor)->getStatusCode());
        self::assertSame(403, $this->postForm($url, ['name' => 'Привет'], FunctionalUserFactory::create())->getStatusCode());
        self::assertSame(403, $this->handleRequest($url, 'POST', ['name' => 'Привет'], cookies: $this->actingAs($editor))->getStatusCode());
    }

    public function testBindingStartsEnabledAndManualModeSurvivesValidationAcrossAllForms(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'binding', 'name' => 'Binding']);
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $collection;
        foreach (['/admin/collections/new', $base . '/items/new', $base . '/sections/new'] as $url) {
            $html = (string) $this->handleRequest($url, cookies: $this->actingAs($user))->getContent();
            $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
            self::assertNotNull($document->querySelector('[data-slug-linked][checked]'));
            self::assertNotNull($document->querySelector('[data-slug-control][hidden]'));
            self::assertNull($document->querySelector('[data-slug-input][readonly]'));
        }
        foreach (['/admin/collections', $base . '/items', $base . '/sections'] as $url) {
            $response = $this->postForm($url, ['name' => '', 'code' => 'my-manual-code', 'sort' => '100', 'per_page' => '20', 'slug_binding_present' => '1'], $user);
            self::assertSame(200, $response->getStatusCode());
            $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
            self::assertNull($document->querySelector('[data-slug-linked][checked]'));
            self::assertSame('my-manual-code', $document->querySelector('[data-slug-input]')->getAttribute('value'));
        }
    }

    public function testOpeningAnExistingItemKeepsItsCanonicalCode(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'canonical', 'name' => 'Canonical']);
        $item = Fixture::insert('collection_items', ['collection_id' => $collection, 'name' => 'Новое название', 'code' => 'old-canonical-code']);
        $html = (string) $this->handleRequest('/admin/collections/' . $collection . '/items/' . $item . '/edit', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        self::assertSame('old-canonical-code', $document->querySelector('[data-slug-input]')->getAttribute('value'));
        self::assertNotNull($document->querySelector('[data-slug-linked][checked]'));
    }
}
