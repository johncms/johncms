<?php

declare(strict_types=1);

namespace Tests\Functional\Downloads;

use Dom\HTMLDocument;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;

/**
 * The collections of the downloads and the search: each marks itself among the chips, and the
 * search tells a query not sent yet from one that found nothing or was not taken.
 */
final class DownloadsCollectionsTest extends FunctionalTestCase
{
    public function testTheSearchTellsItsStatesApart(): void
    {
        Fixture::insert('download__files', ['type' => 2, 'name' => 'squirrel.jpg', 'dir' => '/nowhere', 'rus_name' => 'Squirrel', 'time' => time()]);

        $initial = $this->page('/downloads/search/');
        self::assertSame('page', $initial->querySelector('.site-chip[href="/downloads/search/"]')?->getAttribute('aria-current'));
        self::assertNull($initial->querySelector('#downloads-list-title'), 'Nothing is listed before a search.');
        self::assertNotNull($initial->querySelector('.site-empty-state'));

        $short = $this->page('/downloads/search/?search=s');
        self::assertSame('true', $short->querySelector('input[name="search"]')?->getAttribute('aria-invalid'), 'A query too short is refused beside the field.');
        self::assertSame('s', $short->querySelector('input[name="search"]')->getAttribute('value'));

        $found = $this->page('/downloads/search/?search=squir');
        self::assertCount(1, $found->querySelectorAll('.site-dl-file'));

        $nothing = $this->page('/downloads/search/?search=elephant&id=1');
        self::assertCount(0, $nothing->querySelectorAll('.site-dl-file'));
        self::assertNotNull($nothing->querySelector('#downloads-list-title'));
        self::assertNotNull($nothing->querySelector('.site-empty-state'));
        self::assertTrue($nothing->querySelector('#downloads-search-description')?->hasAttribute('checked'), 'The option stays chosen.');
    }

    public function testTheNewFilesOfACategorySayWhichOne(): void
    {
        // The list of a category is read from its folder, which has to exist.
        $directory = sys_get_temp_dir() . '/johncms-functional-' . bin2hex(random_bytes(4));
        mkdir($directory);
        $category = Fixture::insert('download__category', ['name' => 'docs', 'slug' => 'docs', 'dir' => $directory, 'rus_name' => 'Documents']);

        try {
            $page = $this->page('/downloads/new/?id=' . $category);
        } finally {
            rmdir($directory);
        }

        self::assertSame('page', $page->querySelector('.site-chip[href="/downloads/new/"]')?->getAttribute('aria-current'));
        self::assertSame('/downloads/docs/', $page->querySelector('.site-page-heading__subtitle a')?->getAttribute('href'));
    }

    private function page(string $uri): HTMLDocument
    {
        return HTMLDocument::createFromString((string) $this->handleRequest($uri)->getContent(), LIBXML_NOERROR);
    }
}
