<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Illuminate\Database\Capsule\Manager as DB;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The hidden topics and posts of the forum in the panel: who sees them, how the lists are narrowed
 * and what emptying them deletes.
 */
final class ForumAdminHiddenTest extends FunctionalTestCase
{
    private User $moderator;
    private int $section;
    private int $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, ForumPermissions::DELETED_VIEW]);
        $this->section = Fixture::insert('forum_sections', ['name' => 'A section', 'slug' => 'a-section', 'parent' => 0, 'section_type' => 1, 'sort' => 1]);
        $this->topic = Fixture::insert('forum_topic', ['section_id' => $this->section, 'name' => 'A visible topic', 'slug' => 'a-visible-topic', 'deleted' => 0]);
    }

    public function testTheHiddenPostsAreListedAndTheVisibleOnesAreNot(): void
    {
        $this->post(1, 'A hidden post', true);
        $this->post(1, 'A visible post', false);

        $body = $this->page('/admin/forum/hidden-posts');

        self::assertStringContainsString('A hidden post', $body);
        self::assertStringNotContainsString('A visible post', $body);
    }

    public function testTheHiddenPostsAreNarrowedToAnAuthor(): void
    {
        $this->post(1, 'Of the first author', true);
        $this->post(2, 'Of the second author', true);

        $body = $this->page('/admin/forum/hidden-posts?usort=2');

        self::assertStringContainsString('Of the second author', $body);
        self::assertStringNotContainsString('Of the first author', $body);
    }

    public function testTheHiddenPostsAreNarrowedToATopic(): void
    {
        $other = Fixture::insert('forum_topic', ['section_id' => $this->section, 'name' => 'Another topic', 'slug' => 'another-topic']);
        $this->post(1, 'In the first topic', true);
        $this->post(1, 'In the other topic', true, $other);

        $body = $this->page('/admin/forum/hidden-posts?tsort=' . $other);

        self::assertStringContainsString('In the other topic', $body);
        self::assertStringNotContainsString('In the first topic', $body);
    }

    public function testTheHiddenTopicsAreListedAndNarrowedToASection(): void
    {
        $other = Fixture::insert('forum_sections', ['name' => 'Another section', 'slug' => 'another-section', 'parent' => 0, 'section_type' => 1, 'sort' => 2]);
        Fixture::insert('forum_topic', ['section_id' => $this->section, 'name' => 'Hidden here', 'slug' => 'hidden-here', 'deleted' => 1, 'mod_last_post_date' => time()]);
        Fixture::insert('forum_topic', ['section_id' => $other, 'name' => 'Hidden there', 'slug' => 'hidden-there', 'deleted' => 1, 'mod_last_post_date' => time()]);

        $all = $this->page('/admin/forum/hidden-topics');
        $narrowed = $this->page('/admin/forum/hidden-topics?rsort=' . $other);

        self::assertStringContainsString('Hidden here', $all);
        self::assertStringContainsString('Hidden there', $all);
        self::assertStringNotContainsString('A visible topic', $all);
        self::assertStringContainsString('Hidden there', $narrowed);
        self::assertStringNotContainsString('Hidden here', $narrowed);
    }

    public function testEmptyingTheListDeletesTheHiddenPostsOfTheFilterOnly(): void
    {
        $purger = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, ForumPermissions::DELETED_VIEW, ForumPermissions::HIDDEN_PURGE]);
        $first = $this->post(1, 'Of the first author', true);
        $second = $this->post(2, 'Of the second author', true);
        $visible = $this->post(2, 'A visible post', false);

        $this->postForm('/admin/forum/hidden-posts/delete?usort=2', [], $purger);

        self::assertTrue($this->exists('forum_messages', $first), 'Another author is not in the filter.');
        self::assertFalse($this->exists('forum_messages', $second));
        self::assertTrue($this->exists('forum_messages', $visible), 'A visible post is not hidden.');
    }

    public function testEmptyingTheListTakesThePermission(): void
    {
        $post = $this->post(1, 'A hidden post', true);

        $this->postForm('/admin/forum/hidden-posts/delete', [], $this->moderator);

        self::assertTrue($this->exists('forum_messages', $post));
    }

    public function testEmptyingTheTopicsDeletesTheirPostsAsWell(): void
    {
        $purger = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, ForumPermissions::DELETED_VIEW, ForumPermissions::HIDDEN_PURGE]);
        $hidden = Fixture::insert('forum_topic', ['section_id' => $this->section, 'name' => 'Hidden', 'slug' => 'hidden', 'deleted' => 1]);
        $post = $this->post(1, 'In a hidden topic', false, $hidden);

        $this->postForm('/admin/forum/hidden-topics/delete', [], $purger);

        self::assertFalse($this->exists('forum_topic', $hidden));
        self::assertFalse($this->exists('forum_messages', $post));
        self::assertTrue($this->exists('forum_topic', $this->topic), 'A visible topic stays.');
    }

    private function post(int $userId, string $text, bool $hidden, ?int $topic = null): int
    {
        return Fixture::insert('forum_messages', [
            'topic_id'   => $topic ?? $this->topic,
            'user_id'    => $userId,
            'user_name'  => 'Author ' . $userId,
            'text'       => $text,
            'date'       => time(),
            'deleted'    => $hidden ? 1 : 0,
            'deleted_by' => $hidden ? 'moderator' : '',
        ]);
    }

    private function page(string $uri): string
    {
        return (string) $this->handleRequest($uri, cookies: $this->actingAs($this->moderator))->getContent();
    }

    private function exists(string $table, int $id): bool
    {
        return DB::table($table)->where('id', $id)->exists();
    }
}
