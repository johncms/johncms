<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Forum\Application\Services\ForumFormTokens;
use Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The forms that write — a post, an answer, a new topic, an edit — come back with what was wrong
 * beside their fields and a 422, the text sent kept; nothing is written.
 */
final class ForumWritingFormsTest extends FunctionalTestCase
{
    private int $section;
    private int $topic;
    private int $question;
    private User $author;
    private User $writer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = FunctionalUserFactory::create(['name' => 'Author']);
        $this->writer = FunctionalUserFactory::create(['name' => 'Writer']);
        $this->section = Fixture::insert('forum_sections', ['name' => 'Talk', 'slug' => 'talk', 'parent' => 0, 'description' => '', 'section_type' => 1]);
        $this->topic = $this->topic(closed: false);
        $this->question = $this->post($this->topic, $this->author, '<p>Which <b>editor</b> do you use?</p>');
    }

    public function testATooShortPostComesBackWithItsText(): void
    {
        $response = $this->send('/forum/new-message/' . $this->topic . '/', ['msg' => 'Hi']);

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('is-invalid', (string) $response->getContent());
        self::assertFalse(Capsule::table('forum_messages')->where('user_id', $this->writer->id)->exists());
    }

    public function testAnEmptyAnswerComesBackToItsPage(): void
    {
        $response = $this->send('/forum/reply-message/' . $this->question . '/', ['msg' => '']);

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('action="/forum/reply-message/' . $this->question . '/"', (string) $response->getContent());
    }

    public function testAnEmptyEditKeepsThePost(): void
    {
        $post = $this->post($this->topic, $this->writer, '<p>Mine</p>');

        $response = $this->send('/forum/edit-post/' . $post . '/', ['msg' => '']);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('<p>Mine</p>', Capsule::table('forum_messages')->where('id', $post)->value('text'));
    }

    public function testATopicWithAShortTitleComesBackWithItsFields(): void
    {
        $response = $this->send('/forum/new-topic/' . $this->section . '/', ['th' => 'Hi', 'msg' => '<p>A long enough first post</p>']);

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('value="Hi"', (string) $response->getContent());
        self::assertSame(1, Capsule::table('forum_topic')->count(), 'Only the topic of the set-up.');
    }

    public function testTheAnswerPageOpensAddressingTheAuthorOrQuotingThePost(): void
    {
        $answer = (string) $this->handleRequest('/forum/reply-message/' . $this->question . '/', cookies: $this->actingAs($this->writer))->getContent();
        $quote = (string) $this->handleRequest('/forum/reply-message/' . $this->question . '/?quote=1', cookies: $this->actingAs($this->writer))->getContent();

        self::assertStringContainsString('Author,&amp;nbsp;', $answer, 'The editor opens with the name to address.');
        self::assertStringContainsString('&lt;blockquote&gt;&lt;p&gt;Which &lt;b&gt;editor&lt;/b&gt;', $quote, 'The editor opens with the post quoted.');
        self::assertStringContainsString('action="/forum/reply-message/' . $this->question . '/?quote=1"', $quote);
    }

    public function testNobodyButTheStaffWritesInAClosedTopic(): void
    {
        $closed = $this->topic(closed: true);

        self::assertSame(403, $this->send('/forum/new-message/' . $closed . '/', ['msg' => '<p>Let me in</p>'])->getStatusCode());
        self::assertFalse(Capsule::table('forum_messages')->where('user_id', $this->writer->id)->exists());
    }

    /**
     * @param array<string, string> $fields
     */
    private function send(string $uri, array $fields): Response
    {
        return $this->postForm($uri, $fields + [
            'token'  => $this->container()->get(ForumFormTokens::class)->issue(),
            'submit' => '1',
        ], $this->writer);
    }

    private function topic(bool $closed): int
    {
        $slug = $closed ? 'closed' : 'open';

        return Fixture::insert('forum_topic', [
            'section_id' => $this->section,
            'name'       => 'A topic ' . $slug,
            'slug'       => $slug,
            'user_id'    => $this->author->id,
            'user_name'  => 'Author',
            'deleted'    => 0,
            'closed'     => $closed ? 1 : 0,
            'pinned'     => 0,
        ]);
    }

    private function post(int $topic, User $user, string $text): int
    {
        $id = Fixture::insert('forum_messages', [
            'topic_id'  => $topic,
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'date'      => time(),
            'text'      => $text,
        ]);
        $this->container()->get(ForumTopicStatsRecalculator::class)->recalculate($topic);

        return $id;
    }
}
