<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Http\Session;
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

    /**
     * The form as the page gives it: with the one-time token the page put into the session.
     *
     * @param array<string, string> $fields
     */
    private function send(string $uri, array $fields): Response
    {
        $this->container()->get(Session::class)->set('token', '4242');

        return $this->postForm($uri, $fields + [
            'msg'    => '<p>An answer to that</p>',
            'token'  => '4242',
            'submit' => '1',
        ], $this->writer);
    }

    /** The post the latest message of the writer answers; null when it answers none. */
    private function answeredByLatestOfWriter(): ?int
    {
        $answered = Capsule::table('forum_messages')->where('user_id', $this->writer->id)->orderByDesc('id')->value('reply_to_id');

        return $answered === null ? null : (int) $answered;
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
