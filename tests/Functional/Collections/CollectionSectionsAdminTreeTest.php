<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The sections of a collection in the panel: the whole tree on one page, each section under its
 * parent, with the items of its subtree.
 */
final class CollectionSectionsAdminTreeTest extends FunctionalTestCase
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

    public function testEverySectionStandsUnderItsParent(): void
    {
        $tech = $this->section('Tech', 'tech');
        $php = $this->section('PHP', 'php', $tech);
        $life = $this->section('Life', 'life', sort: 50);

        $content = $this->page();

        self::assertLessThan($this->position($content, $tech), $this->position($content, $life));
        self::assertLessThan($this->position($content, $php), $this->position($content, $tech));
        self::assertStringContainsString('--depth: 1', $this->row($content, $php));
        self::assertStringContainsString('admin-collection-tree__root', $this->row($content, $tech));
    }

    public function testASectionCountsTheItemsUnderItToo(): void
    {
        $tech = $this->section('Tech', 'tech');
        $php = $this->section('PHP', 'php', $tech);
        $this->item('Own', 'own', $tech);
        $this->item('First', 'first', $php);
        $this->item('Second', 'second', $php);

        $row = $this->row($this->page(), $tech);

        self::assertStringContainsString('href="/admin/collections/' . $this->collection . '/items?section=' . $tech . '">3</a>', $row);
        self::assertStringContainsString('Своих: 1', $row);
    }

    public function testDeletingASectionReturnsToTheTree(): void
    {
        $section = $this->section('Doomed', 'doomed');

        $response = $this->handleRequest(
            '/admin/collections/' . $this->collection . '/sections/' . $section . '/delete',
            'POST',
            $this->withCsrfToken(),
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertSame('/admin/collections/' . $this->collection . '/sections', $response->headers->get('Location'));
        self::assertNull(ContentCollectionSection::query()->find($section));
    }

    private function page(): string
    {
        $response = $this->handleRequest('/admin/collections/' . $this->collection . '/sections', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return (string) $response->getContent();
    }

    private function position(string $content, int $section): int
    {
        $position = strpos($content, 'href="/admin/collections/' . $this->collection . '/sections/' . $section . '/edit"');
        self::assertIsInt($position, 'No row of the section ' . $section);

        return $position;
    }

    private function row(string $content, int $section): string
    {
        $link = $this->position($content, $section);
        $start = strrpos(substr($content, 0, $link), '<tr');
        $end = strpos($content, '</tr>', $link);
        self::assertIsInt($start);
        self::assertIsInt($end);

        return substr($content, $start, $end - $start);
    }

    private function section(string $name, string $code, ?int $parent = null, int $sort = 100): int
    {
        return Fixture::insert('collection_sections', [
            'collection_id' => $this->collection, 'parent' => $parent, 'name' => $name, 'code' => $code, 'active' => 1, 'sort' => $sort,
        ]);
    }

    private function item(string $name, string $code, int $section): int
    {
        return Fixture::insert('collection_items', [
            'collection_id' => $this->collection, 'section_id' => $section, 'name' => $name, 'code' => $code, 'active' => 1, 'sort' => 100,
        ]);
    }
}
