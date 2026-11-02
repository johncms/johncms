<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Application\UseCases\GetPublicItemUseCase;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;
use Twig\Markup;

final class CollectionContentRenderingTest extends FunctionalTestCase
{
    private int $collection;
    private int $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = Fixture::insert('collections', ['code' => 'content-pipeline', 'name' => 'Content', 'active' => 1, 'public' => 1, 'settings' => json_encode(['has_sections' => true])]);
        $this->item = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'code' => 'entry', 'name' => 'Entry', 'active' => 1]);
        $this->container()->get(CollectionCodeCacheInterface::class)->invalidate();
    }

    public function testRawApiContentRemainsIntactAndAllHtmlUsesTheSharedPipeline(): void
    {
        $html = '<p>Safe <b>body</b></p><script>alert(1)</script><figure class="media"><oembed url="https://youtu.be/dQw4w9WgXcQ"></oembed></figure>';
        $this->field('rich', 'html');
        $this->field('ordinary', 'string');
        $api = $this->container()->get(CollectionsApiInterface::class);
        $api->patchItem($this->item, new CollectionItemPatchDTO(['previewText' => $html, 'detailText' => $html], ['rich' => $html, 'ordinary' => '<b>escaped field</b>']));
        $raw = $api->getItem($this->item);
        self::assertSame($html, $raw->previewText);
        self::assertSame($html, $raw->detailText);
        self::assertSame($html, $raw->values['rich']);
        $detail = $this->container()->get(GetPublicItemUseCase::class)->format($raw);
        self::assertInstanceOf(Markup::class, $detail->values[0]['values'][0]);
        $response = $this->handleRequest('/content-pipeline/entry.html');
        self::assertSame(200, $response->getStatusCode());
        $rendered = (string) $response->getContent();
        self::assertStringNotContainsString('<script>alert(1)</script>', $rendered);
        self::assertStringNotContainsString('<oembed', $rendered);
        self::assertGreaterThanOrEqual(3, substr_count($rendered, 'youtube.com/embed/dQw4w9WgXcQ'));
        self::assertStringContainsString('&lt;b&gt;escaped field&lt;/b&gt;', $rendered);
    }

    public function testSeoRolesUseAnyFieldCodeAndEmitPlainMetadataInsteadOfContent(): void
    {
        $this->field('custom_heading', 'string', 'meta_title');
        $this->field('custom_summary', 'text', 'meta_description');
        $this->field('custom_terms', 'string', 'meta_keywords');
        $this->container()->get(CollectionsApiInterface::class)->patchItem($this->item, new CollectionItemPatchDTO(values: [
            'custom_heading' => '<b>SEO heading</b>', 'custom_summary' => '<p>SEO description &amp; text</p><script>bad()</script>', 'custom_terms' => '<b>php, cms</b>',
        ]));
        $html = (string) $this->handleRequest('/content-pipeline/entry.html')->getContent();
        self::assertStringContainsString('<title>SEO heading | ', $html);
        self::assertStringContainsString('name="description" content="SEO description &amp; text"', $html);
        self::assertStringContainsString('name="keywords" content="php, cms"', $html);
        self::assertStringNotContainsString('custom_summary', $html);
        self::assertStringNotContainsString('bad()', $html);
        self::assertStringNotContainsString('&lt;p&gt;', $html);
    }

    public function testEmptyHtmlBecomesNullAndDescriptionFallsBackToPlainText(): void
    {
        $api = $this->container()->get(CollectionsApiInterface::class);
        $api->patchItem($this->item, new CollectionItemPatchDTO(['previewText' => '<script>bad()</script>', 'detailText' => '<p>Body description</p>']));
        $detail = $this->container()->get(GetPublicItemUseCase::class)->format($api->getItem($this->item));
        self::assertNull($detail->previewText);
        self::assertSame('Body description', $detail->metaDescription);
        $api->patchItem($this->item, new CollectionItemPatchDTO(['previewText' => '<p>Plain &amp; safe</p>']));
        $html = (string) $this->handleRequest('/content-pipeline/entry.html')->getContent();
        self::assertStringContainsString('name="description" content="Plain &amp; safe"', $html);
    }

    public function testSectionMetadataControlsListingAndUniversalFormPreservesCustomKeys(): void
    {
        $section = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'code' => 'section', 'name' => 'Section', 'active' => 1, 'metadata' => json_encode(['meta_title' => '<b>Section SEO</b>', 'meta_description' => '<p>Section description</p>', 'meta_keywords' => 'cms', 'custom' => 'keep'])]);
        $html = (string) $this->handleRequest('/content-pipeline/section')->getContent();
        self::assertStringContainsString('<title>Section SEO | ', $html);
        self::assertStringContainsString('name="description" content="Section description"', $html);
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $this->collection . '/sections';
        $response = $this->postForm($base, ['id' => $section, 'name' => 'Section', 'code' => 'section', 'active' => '1', 'sort' => '100', 'metadata' => '{"custom":"keep"}', 'meta_title' => 'Edited title', 'meta_description' => 'Edited description', 'meta_keywords' => 'cms, php'], $user);
        self::assertSame(302, $response->getStatusCode());
        $metadata = json_decode(Capsule::table('collection_sections')->where('id', $section)->value('metadata'), true);
        self::assertSame('keep', $metadata['custom']);
        self::assertSame('Edited title', $metadata['meta_title']);
    }

    public function testHtmlFieldsUseTheAdminEditorIncludingMultilineMultipleValues(): void
    {
        $field = $this->field('rich', 'html');
        Capsule::table('collection_fields')->where('id', $field)->update(['multiple' => 1]);
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $this->collection . '/items';
        $values = ["<p>First\nparagraph</p>", '<p>Second paragraph</p>'];
        $response = $this->postForm($base, ['id' => $this->item, 'name' => 'Entry', 'code' => 'entry', 'sort' => '100', 'field_rich' => [...$values, '']], $user);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame($values, $this->container()->get(CollectionsApiInterface::class)->getItem($this->item)->values['rich']);
        $html = (string) $this->handleRequest($base . '/' . $this->item . '/edit', cookies: $this->actingAs($user))->getContent();
        self::assertSame(3, substr_count($html, 'name="field_rich[]"'));
        self::assertGreaterThanOrEqual(5, substr_count($html, 'data-admin-editor')); 
    }

    private function field(string $code, string $type, ?string $role = null): int
    {
        return Fixture::insert('collection_fields', ['collection_id' => $this->collection, 'code' => $code, 'name' => $code, 'type' => $type, 'role' => $role]);
    }
}
