<?php

declare(strict_types=1);

namespace Tests\Functional\Menu;

use Dom\HTMLDocument;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The sections of the site as the main menu and the tiles of the home page show them: where each
 * leads, who sees it, and the badge with what is new in it.
 *
 * A section is told by the address it leads to, not by its words: the site answers in its own
 * language.
 */
final class SectionCountersTest extends FunctionalTestCase
{
    private const array SECTIONS = [
        '/news/', '/forum/', '/guestbook/ga', '/downloads/', '/library/', '/community/', '/album/', '/help/', '/contacts/',
    ];

    public function testAGuestIsShownTheSectionsOfTheSite(): void
    {
        $menu = $this->menu($this->page('/'));

        foreach (self::SECTIONS as $section) {
            self::assertArrayHasKey($section, $menu, $section . ' is missing from the main menu.');
        }

        self::assertArrayNotHasKey('/admin/', $menu);
        self::assertArrayNotHasKey('/guestbook/ga?do=set', $menu);
    }

    public function testTheStaffAreShownThePanelAndTheChatOfTheAdministration(): void
    {
        $menu = $this->menu($this->page('/', FunctionalUserFactory::createSupervisor()));

        self::assertArrayHasKey('/admin/', $menu);
        self::assertArrayHasKey('/guestbook/ga?do=set', $menu);
    }

    public function testNothingNewMeansNoBadge(): void
    {
        $menu = $this->menu($this->page('/'));

        foreach (self::SECTIONS as $section) {
            if ($section !== '/community/') {
                self::assertNull($menu[$section], $section . ' has a badge with nothing new in it.');
            }
        }
    }

    public function testTheTopicsAMemberHasNotReadAreCountedOnTheForum(): void
    {
        Fixture::insert('forum_topic', ['name' => 'Unread', 'last_post_date' => time()]);

        self::assertSame('+ 1', $this->menu($this->page('/', FunctionalUserFactory::create()))['/forum/']);
        self::assertNull($this->menu($this->page('/'))['/forum/'], 'A guest has read nothing and nothing is new for them.');
    }

    public function testTheEntriesOfTheLastDayAreCountedOnTheGuestbookAndOnTheChatOfTheAdministration(): void
    {
        Fixture::insert('guest', ['adm' => 0, 'time' => time(), 'text' => 'Hello']);
        Fixture::insert('guest', ['adm' => 1, 'time' => time(), 'text' => 'Staff only']);
        Fixture::insert('guest', ['adm' => 1, 'time' => time(), 'text' => 'Staff only too']);

        $menu = $this->menu($this->page('/', FunctionalUserFactory::createSupervisor()));

        self::assertSame('+ 1', $menu['/guestbook/ga']);
        self::assertSame('2', $menu['/guestbook/ga?do=set']);
    }

    public function testTheNewFilesArticlesAndPhotosAreCounted(): void
    {
        Fixture::insert('download__files', ['name' => 'New', 'type' => 2, 'time' => time()]);
        Fixture::insert('library_texts', ['name' => 'New', 'premod' => 1, 'time' => time()]);
        Fixture::insert('cms_album_files', ['user_id' => 1, 'access' => 4, 'time' => time()]);

        $menu = $this->menu($this->page('/'));

        self::assertSame('+ 1', $menu['/downloads/']);
        self::assertSame('+ 1', $menu['/library/']);
        self::assertSame('+ 1', $menu['/album/']);
    }

    public function testTheMembersWhoJoinedToday(): void
    {
        FunctionalUserFactory::create();
        FunctionalUserFactory::create();

        self::assertSame('+ 2', $this->menu($this->page('/'))['/community/']);
    }

    /**
     * The menu has no badge for the news, whatever is new in them; the tile of the home page has.
     */
    public function testTheNewsHaveNoBadgeInTheMenu(): void
    {
        Fixture::insert('news_articles', ['name' => 'Fresh', 'code' => 'fresh', 'active' => 1, 'created_at' => date('Y-m-d H:i:s')]);

        self::assertNull($this->menu($this->page('/'))['/news/']);
    }

    public function testTheTilesOfTheHomePageLeadToTheMainSectionsWithTheirCounters(): void
    {
        Fixture::insert('download__files', ['name' => 'New', 'type' => 2, 'time' => time()]);

        $tiles = $this->tiles($this->page('/'));

        self::assertSame(['/news/', '/forum/', '/guestbook/ga', '/downloads/', '/library/', '/community/'], array_keys($tiles));
        self::assertSame('1', $tiles['/downloads/']);
        self::assertNull($tiles['/forum/']);
    }

    private function page(string $uri, ?User $user = null): HTMLDocument
    {
        $response = $this->handleRequest($uri, cookies: $user === null ? [] : $this->actingAs($user));

        return HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    }

    /**
     * @return array<string, ?string> The badge of every line of the main menu, by where it leads.
     */
    private function menu(HTMLDocument $page): array
    {
        $lines = [];

        foreach ($page->querySelectorAll('ul.nav.nav__vertical:not(.ps-2) > li > a') as $link) {
            $badge = $link->querySelector('.badge')?->textContent;
            $lines[(string) $link->getAttribute('href')] = $badge === null ? null : trim($badge);
        }

        return $lines;
    }

    /**
     * @return array<string, ?string> The badge of every tile of the home page, by where it leads.
     */
    private function tiles(HTMLDocument $page): array
    {
        $tiles = [];

        foreach ($page->querySelectorAll('.mobile_tiles a.card') as $tile) {
            $badge = $tile->querySelector('.badge')?->textContent;
            $tiles[(string) $tile->getAttribute('href')] = $badge === null ? null : trim($badge);
        }

        return $tiles;
    }
}
