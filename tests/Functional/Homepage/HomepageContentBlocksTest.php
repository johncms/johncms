<?php

declare(strict_types=1);

namespace Tests\Functional\Homepage;

use Dom\HTMLDocument;
use Johncms\Auth\Authorization\RolePermission;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class HomepageContentBlocksTest extends FunctionalTestCase
{
    public function testEmptyModulesHaveRealEmptyStatesAndOnlineFollowsTheSections(): void
    {
        $page = $this->page();

        foreach (['home-news', 'home-forum', 'home-online'] as $id) {
            self::assertNotNull($page->querySelector('#' . $id . ' .site-empty-state'));
        }
        self::assertSame('home-online', $page->querySelector('.site-home__sections + section')?->id);
        self::assertNotNull($page->querySelector('#home-news a[href="/news/"]'));
        self::assertNotNull($page->querySelector('#home-forum a[href="/forum/"]'));
    }

    public function testHiddenNewsAndClosedForumAreAbsentRatherThanEmpty(): void
    {
        RolePermission::query()->where('permission', 'forum.view')->delete();
        $page = $this->withConfig(['news' => ['homepage_show' => false]], fn (): HTMLDocument => $this->page());

        self::assertNull($page->querySelector('#home-news'));
        self::assertNull($page->querySelector('#home-forum'));
        self::assertNotNull($page->querySelector('#home-online'));
    }

    public function testLatestTopicsExcludeDeletedAndUnpublishedBeforeApplyingTheLimit(): void
    {
        $section = Fixture::insert('forum_sections', ['name' => 'General', 'slug' => 'general']);
        for ($i = 1; $i <= 7; ++$i) {
            Fixture::insert('forum_topic', [
                'section_id' => $section, 'name' => 'Topic ' . $i, 'slug' => 'topic-' . $i,
                'post_count' => 3, 'last_post_date' => time() - $i * 60,
            ]);
        }
        for ($i = 1; $i <= 6; ++$i) {
            Fixture::insert('forum_topic', [
                'section_id' => $section, 'name' => 'Hidden ' . $i, 'post_count' => 0,
                'last_post_date' => time() + $i,
            ]);
        }
        Fixture::insert('forum_topic', ['section_id' => $section, 'name' => 'Deleted', 'deleted' => 1, 'post_count' => 3, 'last_post_date' => time()]);
        Fixture::insert('forum_topic', ['section_id' => 999999, 'name' => 'Orphan', 'post_count' => 3, 'last_post_date' => time()]);

        $page = $this->page();
        $titles = [];
        foreach ($page->querySelectorAll('#home-forum h3 a') as $link) {
            $titles[] = $link->textContent;
            self::assertStringContainsString('/forum/general/', $link->getAttribute('href'));
        }
        self::assertSame(['Topic 1', 'Topic 2', 'Topic 3', 'Topic 4', 'Topic 5'], $titles);
        self::assertNull($page->querySelector('#home-forum .site-empty-state'));
    }

    public function testOnlineShowsPublicNamesWithoutPrivateSessionFieldsOrForbiddenProfileLinks(): void
    {
        FunctionalUserFactory::create(['name' => 'Visible member', 'ip' => '198.51.100.42', 'browser' => 'PrivateBrowser']);
        FunctionalUserFactory::create(['name' => 'Offline member', 'lastdate' => time() - 3600]);

        $page = $this->page();
        $online = $page->querySelector('#home-online');
        self::assertNotNull($online);
        self::assertStringContainsString('Visible member', $online->textContent);
        self::assertStringNotContainsString('Offline member', $online->textContent);
        self::assertStringNotContainsString('198.51.100.42', $online->textContent);
        self::assertStringNotContainsString('PrivateBrowser', $online->textContent);
        self::assertNull($online->querySelector('a[href^="/profile/"]'));
        self::assertCount(1, $online->querySelectorAll('.site-home__online li'));
    }

    public function testTopicDetailsRespectReadStateAndProfileVisibility(): void
    {
        $author = FunctionalUserFactory::create(['name' => 'Last responder']);
        $reader = FunctionalUserFactory::create();
        $section = Fixture::insert('forum_sections', ['name' => 'Support', 'slug' => 'support']);
        $topicId = Fixture::insert('forum_topic', [
            'section_id' => $section, 'name' => 'Detailed topic', 'slug' => 'details', 'post_count' => 4,
            'last_post_date' => time() - 60, 'last_post_author' => $author->id, 'last_post_author_name' => 'Old name',
            'pinned' => 1, 'closed' => 1, 'has_poll' => 1,
        ]);

        $guest = $this->page();
        self::assertNull($guest->querySelector('#home-forum .site-topic--unread'));
        self::assertSame('Support', $guest->querySelector('#home-forum .site-topic__section a')?->textContent);
        self::assertSame('Last responder', $guest->querySelector('#home-forum .site-topic__author span')?->textContent);
        self::assertNull($guest->querySelector('#home-forum a[href^="/profile/"]'));
        self::assertCount(3, $guest->querySelectorAll('#home-forum .site-topic__states svg'));

        $cookies = $this->actingAs($reader);
        $page = HTMLDocument::createFromString((string) $this->handleRequest('/', cookies: $cookies)->getContent(), LIBXML_NOERROR);
        self::assertNotNull($page->querySelector('#home-forum .site-topic--unread'));
        self::assertNotNull($page->querySelector('#home-forum .site-topic__author a[href="/profile/' . $author->id . '"]'));
        $replies = $page->querySelector('#home-forum .site-topic__replies');
        self::assertNotNull($replies);
        self::assertSame('3', trim($replies->textContent));

        Fixture::insert('cms_forum_rdm', ['topic_id' => $topicId, 'user_id' => $reader->id, 'time' => time()]);
        $page = HTMLDocument::createFromString((string) $this->handleRequest('/', cookies: $cookies)->getContent(), LIBXML_NOERROR);
        self::assertNull($page->querySelector('#home-forum .site-topic--unread'));
    }

    public function testADeletedLastAuthorKeepsTheirNameWithoutABrokenProfileLink(): void
    {
        $section = Fixture::insert('forum_sections', ['name' => 'General', 'slug' => 'general']);
        Fixture::insert('forum_topic', [
            'section_id' => $section, 'name' => 'Surviving topic', 'slug' => 'surviving', 'post_count' => 1,
            'last_post_author' => 999999, 'last_post_author_name' => '<b>Former member</b>', 'last_post_date' => time(),
        ]);
        $page = HTMLDocument::createFromString((string) $this->handleRequest('/', cookies: $this->actingAs(FunctionalUserFactory::create()))->getContent(), LIBXML_NOERROR);

        self::assertSame('<b>Former member</b>', $page->querySelector('#home-forum .site-topic__author span')?->textContent);
        self::assertNull($page->querySelector('#home-forum .site-topic__author b'));
        self::assertNull($page->querySelector('#home-forum .site-topic__author a'));
    }

    private function page(): HTMLDocument
    {
        return HTMLDocument::createFromString((string) $this->handleRequest('/', catch: false)->getContent(), LIBXML_NOERROR);
    }
}
