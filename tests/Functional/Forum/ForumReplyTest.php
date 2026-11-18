<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Http\Session;
use Johncms\Modules\Forum\Application\Services\ForumFormTokens;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * An answer keeps the post it answers, whether it is written on the page of the answer or in the
 * form under the topic. A post the writer may not answer leaves the answer without the link
 * rather than refusing it.
 *
 * The answer recalls the post above its text. A hidden one is recalled only as deleted to whoever
 * may not see it; one deleted for good leaves the answer as an ordinary post.
 */
final class ForumReplyTest extends FunctionalTestCase
{
    private int $section;
    private int $topic;
    private User $author;
    private User $writer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = FunctionalUserFactory::create(['name' => 'Author']);
        $this->writer = FunctionalUserFactory::create(['name' => 'Writer']);
        $this->section = Fixture::insert('forum_sections', ['name' => 'Talk', 'slug' => 'talk', 'parent' => 0, 'description' => '', 'section_type' => 1]);
        $this->topic = $this->topic('a-topic');
    }

    public function testAnAnswerFromItsOwnPageKeepsThePostItAnswers(): void
    {
        $answered = $this->post($this->topic, $this->author, 'A question');

        $response = $this->send('/forum/reply-message/' . $answered . '/', []);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame($answered, $this->answeredByLatestOfWriter());
    }

    public function testAnAnswerFromTheFormOfTheTopicKeepsThePostItAnswers(): void
    {
        $answered = $this->post($this->topic, $this->author, 'A question');

        $response = $this->send('/forum/new-message/' . $this->topic . '/', ['reply_to' => (string) $answered]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame($answered, $this->answeredByLatestOfWriter());
    }

    public function testAMessageWithoutAnAnsweredPostAnswersNothing(): void
    {
        $this->post($this->topic, $this->author, 'A question');

        $this->send('/forum/new-message/' . $this->topic . '/', []);

        self::assertNull($this->answeredByLatestOfWriter());
    }

    public function testAPostOfAnotherTopicIsNotAnswered(): void
    {
        $this->post($this->topic, $this->author, 'A question');
        $elsewhere = $this->post($this->topic('another-topic'), $this->author, 'Somewhere else');

        $this->send('/forum/new-message/' . $this->topic . '/', ['reply_to' => (string) $elsewhere]);

        self::assertNull($this->answeredByLatestOfWriter());
    }

    public function testOwnPostIsNotAnswered(): void
    {
        $own = $this->post($this->topic, $this->writer, 'Mine');
        $this->post($this->topic, $this->author, 'Theirs');

        $this->send('/forum/new-message/' . $this->topic . '/', ['reply_to' => (string) $own]);

        self::assertNull($this->answeredByLatestOfWriter());
    }

    public function testAHiddenPostIsAnsweredOnlyByWhoeverSeesIt(): void
    {
        $this->post($this->topic, $this->author, 'A question');
        $hidden = $this->post($this->topic, $this->author, 'Hidden', ['deleted' => 1, 'deleted_by' => 'Alex']);

        $this->send('/forum/new-message/' . $this->topic . '/', ['reply_to' => (string) $hidden]);
        self::assertNull($this->answeredByLatestOfWriter());

        $this->writer = FunctionalUserFactory::createWithPermissions(['forum.post', 'forum.deleted.view'], ['name' => 'Moderator']);
        $this->send('/forum/new-message/' . $this->topic . '/', ['reply_to' => (string) $hidden]);
        self::assertSame($hidden, $this->answeredByLatestOfWriter());
    }

    public function testAnAnswerRecallsThePostItAnswersAboveItsText(): void
    {
        $answered = $this->post($this->topic, $this->author, '<p>Which version should I take?</p>');
        $this->post($this->topic, $this->writer, '<p>The latest one.</p>', ['reply_to_id' => $answered]);

        $page = $this->topicPage($this->writer);

        self::assertStringContainsString('href="/forum/post/' . $answered . '/"', $page);
        self::assertStringContainsString('Which version should I take?</span>', $page, 'The excerpt is plain text.');
        self::assertMatchesRegularExpression('~site-comment__reply-name">Author</span>~', $page);
    }

    public function testAHiddenPostIsRecalledOnlyAsDeletedToWhoeverMayNotSeeIt(): void
    {
        $this->post($this->topic, $this->author, '<p>A question</p>');
        $hidden = $this->post($this->topic, $this->author, '<p>Something regretted</p>', ['deleted' => 1, 'deleted_by' => 'Alex']);
        $this->post($this->topic, $this->writer, '<p>An answer to it</p>', ['reply_to_id' => $hidden]);

        $member = $this->topicPage($this->writer);
        self::assertStringContainsString('<span class="site-comment__reply-to">', $member, 'Recalled without a link.');
        self::assertStringNotContainsString('Something regretted', $member);
        self::assertStringNotContainsString('/forum/post/' . $hidden . '/', $member);

        $moderator = $this->topicPage(FunctionalUserFactory::createWithPermissions([ForumPermissions::DELETED_VIEW]));
        self::assertStringContainsString('href="/forum/post/' . $hidden . '/"', $moderator);
        self::assertStringNotContainsString('<span class="site-comment__reply-to">', $moderator);
    }

    public function testDeletingThePostForGoodKeepsTheAnswerWithoutTheLink(): void
    {
        $this->post($this->topic, $this->author, '<p>A question</p>');
        $answered = $this->post($this->topic, $this->author, '<p>Another one</p>');
        $answer = $this->post($this->topic, $this->writer, '<p>An answer</p>', ['reply_to_id' => $answered, 'deleted' => 1]);

        $this->postForm('/forum/delete-post/' . $answered . '/', ['confirmed' => '1', 'action' => 'delete'], FunctionalUserFactory::createSupervisor());

        self::assertFalse(Capsule::table('forum_messages')->where('id', $answered)->exists());
        self::assertTrue(Capsule::table('forum_messages')->where('id', $answer)->exists(), 'A hidden answer is kept as well.');
        self::assertNull(Capsule::table('forum_messages')->where('id', $answer)->value('reply_to_id'));
        self::assertStringNotContainsString('site-comment__reply-to', $this->topicPage($this->writer));
    }

    public function testEmptyingTheHiddenPostsKeepsTheAnswersWithoutTheLink(): void
    {
        $this->post($this->topic, $this->author, '<p>A question</p>');
        $hidden = $this->post($this->topic, $this->author, '<p>Hidden</p>', ['deleted' => 1, 'deleted_by' => 'Alex']);
        $answer = $this->post($this->topic, $this->writer, '<p>An answer</p>', ['reply_to_id' => $hidden]);
        $purger = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, ForumPermissions::DELETED_VIEW, ForumPermissions::HIDDEN_PURGE]);

        $this->postForm('/admin/forum/hidden/purge', [], $purger);

        self::assertFalse(Capsule::table('forum_messages')->where('id', $hidden)->exists());
        self::assertNull(Capsule::table('forum_messages')->where('id', $answer)->value('reply_to_id'));
    }

    public function testTheFormOfTheTopicCarriesTheAnsweredPost(): void
    {
        $answered = $this->post($this->topic, $this->author, '<p>A question</p>');

        $page = $this->topicPage($this->writer);

        self::assertStringContainsString('name="reply_to" value="" data-comment-reply-field', $page);
        self::assertStringContainsString('href="/forum/reply-message/' . $answered . '/" data-comment-reply', $page, 'Without the script Reply opens the page of the answer.');
    }

    public function testThePostAnAnswerRecallsIsShownOnThePageOfTheTopicItIsOn(): void
    {
        $first = $this->post($this->topic, $this->author, '<p>First</p>');
        $this->post($this->topic, $this->author, '<p>Second</p>');
        $third = $this->post($this->topic, $this->author, '<p>Third</p>');
        $topicUrl = '/forum/talk/a-topic-' . $this->topic . '/';

        // Two posts on a page; the size of a page is a setting of the visitor.
        Capsule::table('users')->where('id', $this->writer->id)->update(['set_user' => serialize(['kmess' => 2])]);

        self::assertStringContainsString('href="' . $topicUrl . '#post-' . $first . '"', $this->postPage($first));
        self::assertStringContainsString('href="' . $topicUrl . '?page=2#post-' . $third . '"', $this->postPage($third));

        Capsule::table('users')->where('id', $this->writer->id)->update(['set_forum' => serialize(['upfp' => 1])]);
        self::assertStringContainsString('href="' . $topicUrl . '?page=2#post-' . $first . '"', $this->postPage($first), 'The newest come first.');
        self::assertStringContainsString('href="' . $topicUrl . '#post-' . $third . '"', $this->postPage($third));
    }

    public function testThePostAnAnswerRecallsOpensWhenTheFilterLeavesItOut(): void
    {
        $answered = $this->post($this->topic, $this->author, '<p>A question</p>');
        $this->post($this->topic, $this->writer, '<p>An answer</p>', ['reply_to_id' => $answered]);
        $session = $this->container()->get(Session::class);
        $session->set('fsort_id', $this->topic);
        $session->set('fsort_users', [$this->writer->id]);

        $topic = $this->topicPage($this->writer);
        self::assertStringNotContainsString('id="post-' . $answered . '"', $topic, 'The filter leaves the post out.');
        self::assertStringContainsString('href="/forum/post/' . $answered . '/"', $topic);

        self::assertStringContainsString('<p>A question</p>', $this->postPage($answered));
    }

    /**
     * The form as the page gives it: with a one-time token the page issued.
     *
     * @param array<string, string> $fields
     */
    private function send(string $uri, array $fields): Response
    {
        return $this->postForm($uri, $fields + [
            'msg'    => '<p>An answer to that</p>',
            'token'  => $this->container()->get(ForumFormTokens::class)->issue(),
            'submit' => '1',
        ], $this->writer);
    }

    /** The post the latest message of the writer answers; null when it answers none. */
    private function answeredByLatestOfWriter(): ?int
    {
        $answered = Capsule::table('forum_messages')->where('user_id', $this->writer->id)->orderByDesc('id')->value('reply_to_id');

        return $answered === null ? null : (int) $answered;
    }

    private function topicPage(User $visitor): string
    {
        $response = $this->handleRequest('/forum/talk/a-topic-' . $this->topic . '/', cookies: $this->actingAs($visitor));
        self::assertSame(200, $response->getStatusCode());

        return (string) $response->getContent();
    }

    private function postPage(int $post): string
    {
        $response = $this->handleRequest('/forum/post/' . $post . '/', cookies: $this->actingAs($this->writer));
        self::assertSame(200, $response->getStatusCode());

        return (string) $response->getContent();
    }

    private function topic(string $slug): int
    {
        return Fixture::insert('forum_topic', [
            'section_id' => $this->section,
            'name'       => 'A topic ' . $slug,
            'slug'       => $slug,
            'user_id'    => $this->author->id,
            'user_name'  => 'Author',
            'deleted'    => 0,
            'closed'     => 0,
            'pinned'     => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function post(int $topic, User $user, string $text, array $attributes = []): int
    {
        $id = Fixture::insert('forum_messages', $attributes + [
            'topic_id'  => $topic,
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'date'      => time() - 7200,
            'text'      => $text,
        ]);
        $this->container()->get(ForumTopicStatsRecalculator::class)->recalculate($topic);

        return $id;
    }
}
