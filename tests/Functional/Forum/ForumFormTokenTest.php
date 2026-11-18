<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Forum\Application\Services\ForumFormTokens;
use Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Every form that writes in the forum carries a one-time token: a form sent twice — a second
 * click, a reload of the answer — saves nothing the second time and answers with a fresh form.
 */
final class ForumFormTokenTest extends FunctionalTestCase
{
    private int $section;
    private int $topic;
    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = FunctionalUserFactory::create(['name' => 'Author']);
        $this->section = Fixture::insert('forum_sections', ['name' => 'Talk', 'slug' => 'talk', 'parent' => 0, 'description' => '', 'section_type' => 1]);
        $this->topic = Fixture::insert('forum_topic', [
            'section_id' => $this->section,
            'name'       => 'A topic',
            'slug'       => 'a-topic',
            'user_id'    => $this->author->id,
            'user_name'  => 'Author',
            'deleted'    => 0,
            'closed'     => 0,
            'pinned'     => 0,
        ]);
    }

    public function testATopicSentTwiceIsCreatedOnce(): void
    {
        $token = $this->token();
        $form = ['th' => 'Which editor?', 'msg' => '<p>Tell me about yours.</p>', 'token' => $token, 'submit' => '1'];

        self::assertSame(302, $this->postForm('/forum/new-topic/' . $this->section . '/', $form, $this->author)->getStatusCode());
        $this->forgetLastPost($this->author);

        $again = $this->postForm('/forum/new-topic/' . $this->section . '/', ['th' => 'Which editor, again?', 'msg' => '<p>Once more.</p>'] + $form, $this->author);

        self::assertSame(422, $again->getStatusCode());
        self::assertSame(2, Capsule::table('forum_topic')->count(), 'The topic of the set-up and the one sent.');
        self::assertMatchesRegularExpression('~name="token" value="[0-9a-f]{32}"~', (string) $again->getContent(), 'The form comes back with a working token.');
    }

    public function testATopicWithoutATokenIsNotCreated(): void
    {
        $response = $this->postForm('/forum/new-topic/' . $this->section . '/', ['th' => 'Which editor?', 'msg' => '<p>Tell me about yours.</p>', 'submit' => '1'], $this->author);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(1, Capsule::table('forum_topic')->count());
    }

    public function testAnEditSentTwiceIsSavedOnce(): void
    {
        $post = $this->post('<p>Draft</p>', time());
        $token = $this->token();

        $first = $this->postForm('/forum/edit-post/' . $post . '/', ['msg' => '<p>Final</p>', 'token' => $token, 'submit' => '1'], $this->author);
        self::assertSame(302, $first->getStatusCode());

        $again = $this->postForm('/forum/edit-post/' . $post . '/', ['msg' => '<p>Final, again</p>', 'token' => $token, 'submit' => '1'], $this->author);

        self::assertSame(422, $again->getStatusCode());
        self::assertSame('<p>Final</p>', Capsule::table('forum_messages')->where('id', $post)->value('text'));
        self::assertStringContainsString('Final, again', (string) $again->getContent(), 'The text sent is kept in the form.');
    }

    public function testAMessageSentTwiceIsSavedOnce(): void
    {
        $this->post('<p>A question</p>');
        $writer = FunctionalUserFactory::create(['name' => 'Writer']);
        $token = $this->token();

        $this->postForm('/forum/new-message/' . $this->topic . '/', ['msg' => '<p>An answer</p>', 'token' => $token, 'submit' => '1'], $writer);
        $this->forgetLastPost($writer);
        $this->postForm('/forum/new-message/' . $this->topic . '/', ['msg' => '<p>Another answer</p>', 'token' => $token, 'submit' => '1'], $writer);

        self::assertSame(['<p>An answer</p>'], Capsule::table('forum_messages')->where('user_id', $writer->id)->pluck('text')->all());
    }

    public function testTokensOfFormsOpenTogetherAreAllValid(): void
    {
        $tokens = $this->container()->get(ForumFormTokens::class);
        $first = $tokens->issue();
        $second = $tokens->issue();

        self::assertTrue($tokens->consume($first));
        self::assertTrue($tokens->consume($second));
        self::assertFalse($tokens->consume($first), 'A token is spent once.');
    }

    private function token(): string
    {
        return $this->container()->get(ForumFormTokens::class)->issue();
    }

    /** The antiflood would refuse the second form on its own: the token is what is tested. */
    private function forgetLastPost(User $user): void
    {
        Capsule::table('users')->where('id', $user->id)->update(['lastpost' => 0]);
    }

    private function post(string $text, ?int $date = null): int
    {
        $id = Fixture::insert('forum_messages', [
            'topic_id'  => $this->topic,
            'user_id'   => $this->author->id,
            'user_name' => 'Author',
            'date'      => $date ?? time() - 7200,
            'text'      => $text,
        ]);
        $this->container()->get(ForumTopicStatsRecalculator::class)->recalculate($this->topic);

        return $id;
    }
}
