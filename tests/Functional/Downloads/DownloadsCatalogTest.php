<?php

declare(strict_types=1);

namespace Tests\Functional\Downloads;

use Dom\HTMLDocument;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The catalogue: the cards of the categories, the files of a level and their order, which the
 * address keeps.
 */
final class DownloadsCatalogTest extends FunctionalTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/johncms-functional-' . bin2hex(random_bytes(4));
        mkdir($this->directory . '/docs/old', 0777, true);
    }

    protected function tearDown(): void
    {
        rmdir($this->directory . '/docs/old');
        rmdir($this->directory . '/docs');
        rmdir($this->directory);

        parent::tearDown();
    }

    public function testACategoryCardSaysWhenItsNewestFileCame(): void
    {
        $docs = Fixture::insert('download__category', ['name' => 'docs', 'slug' => 'docs', 'dir' => $this->directory . '/docs', 'rus_name' => 'Documents', 'desc' => 'Manuals', 'total' => 1, 'sort' => 1]);
        Fixture::insert('download__category', ['refid' => $docs, 'name' => 'old', 'slug' => 'old', 'dir' => $this->directory . '/docs/old', 'rus_name' => 'Old', 'sort' => 1]);
        // The newest file sits in the subcategory: the card of its parent still counts it.
        Fixture::insert('download__files', ['type' => 2, 'name' => 'a.zip', 'dir' => $this->directory . '/docs/old', 'rus_name' => 'A', 'time' => mktime(12, 0, 0, 2, 4, 2026)]);

        $page = $this->page('/downloads/');
        $card = $page->querySelector('.site-dl-card');

        self::assertNotNull($card);
        self::assertSame('/downloads/docs/', $card->querySelector('a.stretched-link')?->getAttribute('href'));
        self::assertStringContainsString('Manuals', $card->textContent);
        self::assertStringContainsString('2026', (string) $card->querySelector('.site-dl-card__meta')?->textContent);
        self::assertNull($card->querySelector('.site-dl-card__actions'), 'A guest gets no buttons of a moderator.');

        $moderated = $this->page('/downloads/', FunctionalUserFactory::createSupervisor());
        $actions = $moderated->querySelector('.site-dl-card__actions');
        self::assertNotNull($actions);
        self::assertNull($actions->querySelector('form[action$="/sort"]'), 'A single category has nothing to be reordered with.');
        self::assertNotNull($actions->querySelector('a[href$="/edit"]'));
    }

    public function testTheOrderOfTheFilesComesFromTheAddress(): void
    {
        $docs = Fixture::insert('download__category', ['name' => 'docs', 'slug' => 'docs', 'dir' => $this->directory . '/docs', 'rus_name' => 'Documents', 'total' => 2]);
        Fixture::insert('download__files', ['type' => 2, 'refid' => $docs, 'name' => 'b.zip', 'dir' => $this->directory . '/docs', 'rus_name' => 'Beta', 'time' => 200]);
        Fixture::insert('download__files', ['type' => 2, 'refid' => $docs, 'name' => 'a.zip', 'dir' => $this->directory . '/docs', 'rus_name' => 'Alpha', 'time' => 100]);
        Fixture::insert('download__files', ['type' => 3, 'refid' => $docs, 'name' => 'c.zip', 'dir' => $this->directory . '/docs', 'rus_name' => 'Pending', 'time' => 300]);

        self::assertSame(['Beta', 'Alpha'], $this->names($this->page('/downloads/docs/')), 'The newest first by default; a pending file is not listed.');
        self::assertSame(['Alpha', 'Beta'], $this->names($this->page('/downloads/docs/?sort=name&order=asc')));
        self::assertSame(['Beta', 'Alpha'], $this->names($this->page('/downloads/docs/?sort=nonsense')), 'An unknown order falls back to the default.');

        $page = $this->page('/downloads/docs/?sort=name');
        self::assertSame('true', $page->querySelector('.site-segmented__item[href^="/downloads/docs/?sort=name"]')?->getAttribute('aria-current'));
        self::assertSame('/downloads/docs/?sort=name&order=asc#downloads-files', $page->querySelector('.site-downloads__order')?->getAttribute('href'));
    }

    public function testTheHeadingOfACategoryCountsItsOwnFiles(): void
    {
        $docs = Fixture::insert('download__category', ['name' => 'docs', 'slug' => 'docs', 'dir' => $this->directory . '/docs', 'rus_name' => 'Documents', 'total' => 3]);
        Fixture::insert('download__category', ['refid' => $docs, 'name' => 'old', 'slug' => 'old', 'dir' => $this->directory . '/docs/old', 'rus_name' => 'Old', 'total' => 2]);
        Fixture::insert('download__files', ['type' => 2, 'refid' => $docs, 'name' => 'a.zip', 'dir' => $this->directory . '/docs', 'rus_name' => 'A']);

        $stat = trim((string) $this->page('/downloads/docs/')->querySelector('.site-page-stat')?->textContent);

        // The language of the page depends on the session, so the numbers are what is checked.
        self::assertSame(['1', '1'], preg_match_all('/\d+/', $stat, $numbers) ? $numbers[0] : [], 'One subsection and one file of its own: the files of the subsections are counted on their cards.');
    }

    public function testDeletingASectionWarnsAboutTheFilesWaitingForAModerator(): void
    {
        $docs = Fixture::insert('download__category', ['name' => 'docs', 'slug' => 'docs', 'dir' => $this->directory . '/docs', 'rus_name' => 'Documents', 'total' => 0]);
        Fixture::insert('download__files', ['type' => 3, 'refid' => $docs, 'name' => 'a.zip', 'dir' => $this->directory . '/docs', 'rus_name' => 'Pending']);
        $moderator = FunctionalUserFactory::createSupervisor();

        $card = $this->page('/downloads/', $moderator)->querySelector('.site-dl-card__actions form[action$="/delete"]');
        $menu = $this->page('/downloads/docs/', $moderator)->querySelector('.site-popover__panel form[action$="/delete"]');

        foreach ([$card, $menu] as $form) {
            self::assertMatchesRegularExpression('/\b1\b/u', (string) $form?->getAttribute('data-confirm-text'), 'A pending file is deleted with the section, so the question counts it.');
        }
    }

    private function page(string $uri, mixed $user = null): HTMLDocument
    {
        $cookies = $user === null ? [] : $this->actingAs($user);

        return HTMLDocument::createFromString((string) $this->handleRequest($uri, cookies: $cookies)->getContent(), LIBXML_NOERROR);
    }

    /** @return list<string> */
    private function names(HTMLDocument $page): array
    {
        $names = [];
        foreach ($page->querySelectorAll('.site-dl-file__name a') as $link) {
            $names[] = trim($link->textContent);
        }

        return $names;
    }
}
