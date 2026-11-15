<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The rows of the root of the forum and of a category: what a section holds is counted over the
 * sections under it, and the last topic is where the last post was written. Hidden topics count,
 * and may be the last one, only for whoever may see them.
 */
final class ForumSectionRowsTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $community = $this->section('Community', 'community', 0, 0);
        $talk = $this->section('Talk', 'talk', $community, 1);
        $news = $this->section('News', 'news', $community, 1);
        $projects = $this->section('Projects', 'projects', 0, 0);
        $mods = $this->section('Mods', 'mods', $projects, 0);
        $themes = $this->section('Themes', 'themes', $mods, 1);

        $this->topic($talk, 'Hello everybody', 100, posts: 3);
        $this->topic($news, 'Release notes', 200, posts: 2);
        $this->topic($news, 'A hidden topic', 300, posts: 4, hidden: true);
        $this->topic($themes, 'A dark theme', 150, posts: 5);
    }

    public function testAGuestCountsOnlyWhatIsInSight(): void
    {
        $page = $this->page('/forum/');

        self::assertSame('2', $this->topicsOf($page, 'Community'), 'Two topics in sight under Community.');
        self::assertStringContainsString('Release notes', $page, 'The last topic in sight.');
        self::assertStringNotContainsString('A hidden topic', $page);
        self::assertMatchesRegularExpression('~ 3 \S+ · 10 \S+ ~u', $this->squash($page), 'The heading sums the forum.');
    }

    public function testWhoeverMaySeeHiddenTopicsCountsThem(): void
    {
        $page = $this->page('/forum/', FunctionalUserFactory::createWithPermissions(['forum.deleted.view'])->id);

        self::assertSame('3', $this->topicsOf($page, 'Community'));
        self::assertStringContainsString('A hidden topic', $page, 'A hidden topic may be the last one for them.');
    }

    public function testASectionCountsTheSectionsUnderItAtAnyDepth(): void
    {
        $page = $this->page('/forum/');

        self::assertSame('1', $this->topicsOf($page, 'Projects'));
        self::assertStringContainsString('A dark theme', $page);
        self::assertStringContainsString('href="/forum/projects/mods/"', $page, 'The section under it is listed when the row opens.');
    }

    public function testACategoryListsItsSections(): void
    {
        $page = $this->page('/forum/community/');

        self::assertSame('1', $this->topicsOf($page, 'Talk'));
        self::assertSame('1', $this->topicsOf($page, 'News'));
        self::assertStringContainsString('href="/forum/community/news/release-notes-', $page);
    }

    private function page(string $address, ?int $as = null): string
    {
        $response = $this->handleRequest($address, cookies: $as === null ? [] : $this->actingAs($as));
        self::assertSame(200, $response->getStatusCode());

        return (string) $response->getContent();
    }

    /** The number of topics a row shows next to the title of its section. */
    private function topicsOf(string $page, string $section): string
    {
        $pattern = '~>' . preg_quote($section, '~') . '</a>\s*<span class="site-forum-row__count"[^>]*>(\d+)</span>~';
        self::assertSame(1, preg_match($pattern, $page, $matches), 'A row of ' . $section . ' is on the page.');

        return $matches[1];
    }

    private function squash(string $page): string
    {
        return (string) preg_replace('~\s+~', ' ', strip_tags($page));
    }

    private function section(string $name, string $slug, int $parent, int $type): int
    {
        return Fixture::insert('forum_sections', [
            'name'         => $name,
            'slug'         => $slug,
            'parent'       => $parent,
            'description'  => '',
            'section_type' => $type,
        ]);
    }

    private function topic(int $section, string $name, int $lastDate, int $posts, bool $hidden = false): void
    {
        Fixture::insert('forum_topic', [
            'section_id'                => $section,
            'name'                      => $name,
            'slug'                      => strtolower(str_replace(' ', '-', $name)),
            'user_id'                   => 1,
            'user_name'                 => 'Author',
            'deleted'                   => $hidden ? 1 : 0,
            'post_count'                => $posts,
            'mod_post_count'            => $posts,
            'last_post_date'            => $lastDate,
            'last_post_author_name'     => 'Author',
            'mod_last_post_date'        => $lastDate,
            'mod_last_post_author_name' => 'Author',
        ]);
    }
}
