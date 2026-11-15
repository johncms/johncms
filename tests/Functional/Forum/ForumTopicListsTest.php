<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The listings of topics: a section, the unread ones, a period and the latest. Hidden topics show
 * only to whoever may see them, with the buttons each one's own permission allows.
 */
final class ForumTopicListsTest extends FunctionalTestCase
{
    private int $section;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Fixture::insert('forum_sections', ['name' => 'Community', 'slug' => 'community', 'parent' => 0, 'description' => '', 'section_type' => 0]);
        $this->section = Fixture::insert('forum_sections', ['name' => 'Talk', 'slug' => 'talk', 'parent' => $category, 'description' => '', 'section_type' => 1]);
    }

    public function testASectionListsThePinnedTopicsFirst(): void
    {
        $this->topic('An old pinned topic', time() - 3600, pinned: true);
        $this->topic('A fresh topic', time());

        $page = $this->page('/forum/community/talk/');

        self::assertLessThan(strpos($page, 'A fresh topic'), strpos($page, 'An old pinned topic'));
    }

    public function testTheCountLeadsToTheLastPage(): void
    {
        $topic = $this->topic('A long topic', time(), posts: 25);

        $page = $this->page('/forum/community/talk/');

        self::assertMatchesRegularExpression('~href="/forum/community/talk/a-long-topic-' . $topic . '/\?page=\d+"~', $page);
    }

    public function testAHiddenTopicShowsOnlyToWhoeverMaySeeIt(): void
    {
        $this->topic('A hidden topic', time(), hidden: true);

        self::assertStringNotContainsString('A hidden topic', $this->page('/forum/community/talk/'));
        self::assertStringContainsString('A hidden topic', $this->page('/forum/community/talk/', FunctionalUserFactory::createWithPermissions(['forum.deleted.view'])->id));
    }

    public function testTheButtonsOverAHiddenTopicFollowTheirOwnPermissions(): void
    {
        $topic = $this->topic('A hidden topic', time(), hidden: true);

        $restorer = $this->page('/forum/community/talk/', FunctionalUserFactory::createWithPermissions(['forum.deleted.view', 'forum.topic.moderate'])->id);
        self::assertStringContainsString('action="/forum/restore-topic/' . $topic . '/"', $restorer);
        self::assertStringNotContainsString('action="/forum/delete-topic/' . $topic . '/"', $restorer);

        $supervisor = $this->page('/forum/community/talk/', FunctionalUserFactory::createSupervisor()->id);
        self::assertStringContainsString('action="/forum/restore-topic/' . $topic . '/"', $supervisor);
        self::assertStringContainsString('action="/forum/delete-topic/' . $topic . '/"', $supervisor);
    }

    public function testATopicNeverRecountedDoesNotBreakTheListing(): void
    {
        Fixture::insert('forum_topic', [
            'section_id' => $this->section,
            'name'       => 'A topic never recounted',
            'slug'       => 'never-recounted',
            'user_id'    => 1,
            'user_name'  => 'Author',
            'deleted'    => 0,
        ]);

        self::assertStringContainsString('A topic never recounted', $this->page('/forum/community/talk/', FunctionalUserFactory::createSupervisor()->id));
    }

    public function testTheListingsAcrossTheForumNameTheSectionOfEachTopic(): void
    {
        $this->topic('A fresh topic', time());

        $latest = $this->page('/forum/latest-topics/');
        self::assertStringContainsString('A fresh topic', $latest);
        self::assertStringContainsString('<a href="/forum/community/talk/">Talk</a>', $latest);

        $member = FunctionalUserFactory::create();
        self::assertStringContainsString('A fresh topic', $this->page('/forum/unread/', $member->id));
        self::assertStringContainsString('A fresh topic', $this->page('/forum/topics-period/?vr=2', $member->id));
    }

    public function testEveryTopicIsMarkedAsReadOnlyByAConfirmedForm(): void
    {
        $this->topic('A fresh topic', time());
        $member = FunctionalUserFactory::create();

        self::assertSame(409, $this->postForm('/forum/unread/mark-read/', [], $member)->getStatusCode());
        self::assertStringContainsString('A fresh topic', $this->page('/forum/unread/', $member->id));

        $marked = $this->postForm('/forum/unread/mark-read/', ['confirmed' => '1'], $member);

        self::assertSame('/forum/unread/', $marked->headers->get('Location'));
        self::assertStringNotContainsString('A fresh topic', $this->page('/forum/unread/', $member->id));
    }

    private function page(string $address, ?int $as = null): string
    {
        $response = $this->handleRequest($address, cookies: $as === null ? [] : $this->actingAs($as));
        self::assertSame(200, $response->getStatusCode(), $address);

        return (string) $response->getContent();
    }

    private function topic(string $name, int $lastDate, int $posts = 1, bool $pinned = false, bool $hidden = false): int
    {
        return Fixture::insert('forum_topic', [
            'section_id'                => $this->section,
            'name'                      => $name,
            'slug'                      => strtolower(str_replace(' ', '-', $name)),
            'user_id'                   => 1,
            'user_name'                 => 'Author',
            'deleted'                   => $hidden ? 1 : 0,
            'deleted_by'                => $hidden ? 'Alex' : null,
            'pinned'                    => $pinned ? 1 : 0,
            'closed'                    => 0,
            'has_poll'                  => 0,
            'post_count'                => $posts,
            'mod_post_count'            => $posts,
            'last_post_date'            => $lastDate,
            'last_post_author_name'     => 'Author',
            'mod_last_post_date'        => $lastDate,
            'mod_last_post_author_name' => 'Author',
        ]);
    }
}
