<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Illuminate\Database\Capsule\Manager as DB;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
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

        $body = $this->page('/admin/forum/hidden');

        self::assertStringContainsString('A hidden post', $body);
        self::assertStringNotContainsString('A visible post', $body);
    }

    public function testTheHiddenPostsAreNarrowedToAnAuthor(): void
    {
        $this->post(1, 'Of the first author', true);
        $this->post(2, 'Of the second author', true);

        $body = $this->page('/admin/forum/hidden?author=2');

        self::assertStringContainsString('Of the second author', $body);
        self::assertStringNotContainsString('Of the first author', $body);
    }

    public function testTheHiddenPostsAreNarrowedToATopic(): void
    {
        $other = Fixture::insert('forum_topic', ['section_id' => $this->section, 'name' => 'Another topic', 'slug' => 'another-topic']);
        $this->post(1, 'In the first topic', true);
        $this->post(1, 'In the other topic', true, $other);

        $body = $this->page('/admin/forum/hidden?topic=' . $other);

        self::assertStringContainsString('In the other topic', $body);
        self::assertStringNotContainsString('In the first topic', $body);
    }

    public function testTheHiddenTopicsAreListedAndNarrowedToASection(): void
    {
        $other = Fixture::insert('forum_sections', ['name' => 'Another section', 'slug' => 'another-section', 'parent' => 0, 'section_type' => 1, 'sort' => 2]);
        Fixture::insert('forum_topic', ['section_id' => $this->section, 'name' => 'Hidden here', 'slug' => 'hidden-here', 'deleted' => 1, 'mod_last_post_date' => time()]);
        Fixture::insert('forum_topic', ['section_id' => $other, 'name' => 'Hidden there', 'slug' => 'hidden-there', 'deleted' => 1, 'mod_last_post_date' => time()]);

        $all = $this->page('/admin/forum/hidden?tab=topics');
        $narrowed = $this->page('/admin/forum/hidden?tab=topics&section=' . $other);

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

        $this->postForm('/admin/forum/hidden/purge?author=2', [], $purger);

        self::assertTrue($this->exists('forum_messages', $first), 'Another author is not in the filter.');
        self::assertFalse($this->exists('forum_messages', $second));
        self::assertTrue($this->exists('forum_messages', $visible), 'A visible post is not hidden.');
    }

    public function testEmptyingTheListTakesThePermission(): void
    {
        $post = $this->post(1, 'A hidden post', true);

        $response = $this->postForm('/admin/forum/hidden/purge', [], $this->moderator);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertTrue($this->exists('forum_messages', $post));
    }

    public function testEmptyingTheTopicsDeletesTheirPostsAsWell(): void
    {
        $purger = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, ForumPermissions::DELETED_VIEW, ForumPermissions::HIDDEN_PURGE]);
        $hidden = Fixture::insert('forum_topic', ['section_id' => $this->section, 'name' => 'Hidden', 'slug' => 'hidden', 'deleted' => 1]);
        $post = $this->post(1, 'In a hidden topic', false, $hidden);

        $this->postForm('/admin/forum/hidden/purge?tab=topics', [], $purger);

        self::assertFalse($this->exists('forum_topic', $hidden));
        self::assertFalse($this->exists('forum_messages', $post));
        self::assertTrue($this->exists('forum_topic', $this->topic), 'A visible topic stays.');
    }

    /**
     * The hidden content is for whoever may see it; opening the panel is not enough. Without the
     * right the list used to come out empty rather than refused.
     */
    public function testTheHiddenContentTakesTheRightToSeeIt(): void
    {
        $admin = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);

        $list = $this->handleRequest('/admin/forum/hidden', cookies: $this->actingAs($admin));
        $forum = (string) $this->handleRequest('/admin/forum', cookies: $this->actingAs($admin))->getContent();

        self::assertSame(Response::HTTP_FORBIDDEN, $list->getStatusCode());
        self::assertStringNotContainsString('/admin/forum/hidden', $forum, 'The forum does not lead there either.');
    }

    public function testTheSearchLooksInTheAuthorAndInTheTopic(): void
    {
        $other = Fixture::insert('forum_topic', ['section_id' => $this->section, 'name' => 'Spam corner', 'slug' => 'spam-corner']);
        $this->post(1, 'Found by the topic', true, $other);
        $this->post(7, 'Found by the author', true);
        $this->post(1, 'Not found', true);

        $body = $this->page('/admin/forum/hidden?q=' . rawurlencode('spam'));
        $byAuthor = $this->page('/admin/forum/hidden?q=' . rawurlencode('Author 7'));

        self::assertStringContainsString('Found by the topic', $body);
        self::assertStringNotContainsString('Not found', $body);
        self::assertStringContainsString('Found by the author', $byAuthor);
        self::assertStringNotContainsString('Not found', $byAuthor);
    }

    public function testTheListIsNarrowedToWhoHidIt(): void
    {
        $this->post(1, 'Hidden by the moderator', true);
        Fixture::insert('forum_messages', ['topic_id' => $this->topic, 'user_id' => 1, 'user_name' => 'Author 1', 'text' => 'Hidden by somebody else', 'date' => time(), 'deleted' => 1, 'deleted_by' => 'somebody']);

        $body = $this->page('/admin/forum/hidden?by=somebody');

        self::assertStringContainsString('Hidden by somebody else', $body);
        self::assertStringNotContainsString('Hidden by the moderator', $body);
        self::assertMatchesRegularExpression('~class="dropdown-item[^"]*" href="/admin/forum/hidden\?by=somebody"~', $this->page('/admin/forum/hidden'), 'Who hid something is offered in the filter.');
    }

    public function testTheLiveListGetsTheRegionsAlone(): void
    {
        $this->post(1, 'A hidden post', true);

        $response = $this->handleRequest('/admin/forum/hidden', server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'], cookies: $this->actingAs($this->moderator));

        $body = (string) $response->getContent();
        self::assertStringContainsString('data-live-list-region="results"', $body);
        self::assertStringContainsString('A hidden post', $body);
        self::assertStringNotContainsString('<html', $body);
    }

    public function testTheDialogSaysHowMuchGoes(): void
    {
        $purger = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, ForumPermissions::DELETED_VIEW, ForumPermissions::HIDDEN_PURGE]);
        $this->post(2, 'One', true);
        $this->post(2, 'Two', true);
        $this->post(3, 'Of another author', true);

        $body = (string) $this->handleRequest('/admin/forum/hidden/purge?author=2', server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'], cookies: $this->actingAs($purger))->getContent();

        self::assertStringContainsString('2 скрытых сообщения', $body);
        self::assertStringContainsString('action="/admin/forum/hidden/purge?author=2"', $body, 'The form keeps the filter.');
    }

    public function testEmptyingTheListDeletesTheFilesMarkedDeletedAsWell(): void
    {
        $purger = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, ForumPermissions::DELETED_VIEW, ForumPermissions::HIDDEN_PURGE]);
        $post = $this->post(1, 'With a file', true);
        $file = Fixture::insert('cms_forum_files', ['post' => $post, 'topic' => $this->topic, 'filename' => 'gone.txt', 'del' => 1]);

        $response = $this->postForm('/admin/forum/hidden/purge', [], $purger);

        self::assertSame('/admin/forum/hidden', $response->headers->get('Location'));
        self::assertFalse($this->exists('cms_forum_files', $file));
        self::assertStringContainsString('Скрытые сообщения удалены', $this->page('/admin/forum/hidden'));
    }

    public function testAPostShowsAnExcerptOfItsTextAndWhereItCameFrom(): void
    {
        $supervisor = FunctionalUserFactory::createSupervisor();
        Fixture::insert('forum_messages', [
            'topic_id'   => $this->topic,
            'user_id'    => 1,
            'user_name'  => 'Author 1',
            'text'       => '<p>Fish &amp; chips</p><p>[b]Bold[/b]</p>',
            'date'       => time(),
            'ip'         => ip2long('172.17.0.1'),
            'deleted'    => 1,
            'deleted_by' => 'moderator',
        ]);

        $body = (string) $this->handleRequest('/admin/forum/hidden', cookies: $this->actingAs($supervisor))->getContent();

        self::assertStringContainsString("Fish &amp; chips\nBold", $body, 'The markup is taken out and the text escaped once.');
        self::assertStringContainsString('>172.17.0.1</a>', $body);
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
