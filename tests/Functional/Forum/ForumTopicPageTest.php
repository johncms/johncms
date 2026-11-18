<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The posts of a topic page: who may change which of them, and what only some visitors see.
 */
final class ForumTopicPageTest extends FunctionalTestCase
{
    private int $topic;
    private int $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = FunctionalUserFactory::create(['name' => 'Author'])->id;
        $section = Fixture::insert('forum_sections', [
            'name'         => 'Talk',
            'slug'         => 'talk',
            'parent'       => 0,
            'description'  => '',
            'section_type' => 1,
            'access'       => 2,
        ]);
        $this->topic = Fixture::insert('forum_topic', [
            'section_id' => $section,
            'name'       => 'A topic',
            'slug'       => 'a-topic',
            'user_id'    => $this->author,
            'user_name'  => 'Author',
            'deleted'    => 0,
            'closed'     => 0,
            'pinned'     => 0,
        ]);
    }

    public function testTheAuthorChangesTheFirstPostOfTheTopicOnlyAndNotTheOneOpeningAnotherPage(): void
    {
        $old = time() - 3600;
        $first = $this->post('The opening post', ['date' => $old]);
        $other = FunctionalUserFactory::create(['name' => 'Other'])->id;
        for ($i = 0; $i < 9; $i++) {
            $this->post('A reply ' . $i, ['user_id' => $other, 'user_name' => 'Other', 'date' => $old]);
        }
        $opensSecondPage = $this->post('Back again', ['date' => $old]);
        $this->post('The last word', ['user_id' => $other, 'user_name' => 'Other', 'date' => $old]);

        $firstPage = $this->page($this->author);
        self::assertStringContainsString('/forum/edit-post/' . $first . '/', $firstPage);

        $secondPage = $this->page($this->author, 2);
        self::assertStringContainsString('id="post-' . $opensSecondPage . '"', $secondPage);
        self::assertStringNotContainsString('/forum/edit-post/' . $opensSecondPage . '/', $secondPage);
    }

    public function testWhereAPostCameFromIsShownToThoseWhoMaySeeIt(): void
    {
        $this->post('A post', ['user_agent' => 'Firefox']);

        self::assertStringNotContainsString('class="site-popover site-meta"', $this->page(FunctionalUserFactory::create()));
        self::assertStringContainsString('class="site-popover site-meta"', $this->page(FunctionalUserFactory::createSupervisor()));
    }

    public function testAHiddenPostIsShownMarkedToThoseWhoSeeTheHiddenOnes(): void
    {
        $this->post('A visible post');
        $this->post('A hidden post', ['deleted' => 1, 'deleted_by' => 'Alex']);

        $member = $this->page(FunctionalUserFactory::create());
        self::assertStringContainsString('A visible post', $member);
        self::assertStringNotContainsString('A hidden post', $member);

        $moderator = $this->page(FunctionalUserFactory::createSupervisor());
        self::assertStringContainsString('A hidden post', $moderator);
        self::assertStringContainsString('site-forum-post--deleted', $moderator);
        self::assertStringContainsString('Alex', $moderator);
    }

    public function testAMemberAnswersOnThePageUnlessTheTopicIsClosed(): void
    {
        $this->post('A post');
        $member = FunctionalUserFactory::create();

        self::assertStringContainsString('name="msg"', $this->page($member));

        ForumTopic::query()->whereKey($this->topic)->update(['closed' => 1]);

        self::assertStringNotContainsString('name="msg"', $this->page($member));
    }

    public function testThePollShowsItsTextEscapedOnce(): void
    {
        $this->post('A post');
        ForumTopic::query()->whereKey($this->topic)->update(['has_poll' => 1]);
        Fixture::insert('cms_forum_vote', ['type' => 1, 'time' => time(), 'topic' => $this->topic, 'name' => 'Tom & Jerry?', 'count' => 0]);
        Fixture::insert('cms_forum_vote', ['type' => 2, 'time' => 0, 'topic' => $this->topic, 'name' => 'Cats <b>and</b> dogs', 'count' => 0]);

        $html = $this->page(FunctionalUserFactory::create());

        self::assertStringContainsString('Tom &amp; Jerry?', $html);
        self::assertStringContainsString('Cats &lt;b&gt;and&lt;/b&gt; dogs', $html);
        self::assertStringNotContainsString('&amp;amp;', $html);
    }

    public function testAGuestSeesTheResultsOfThePoll(): void
    {
        $this->post('A post');
        ForumTopic::query()->whereKey($this->topic)->update(['has_poll' => 1]);
        Fixture::insert('cms_forum_vote', ['type' => 1, 'time' => time(), 'topic' => $this->topic, 'name' => 'Tea or coffee?', 'count' => 0]);
        Fixture::insert('cms_forum_vote', ['type' => 2, 'time' => 0, 'topic' => $this->topic, 'name' => 'Tea', 'count' => 0]);

        // A guest has no vote of their own to look up: the page must not ask for it.
        $response = $this->handleRequest('/forum/talk/a-topic-' . $this->topic . '/');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Tea or coffee?', (string) $response->getContent());
        self::assertStringNotContainsString('action="/forum/poll-vote/', (string) $response->getContent());
    }

    private function page(User|int $as, int $page = 1): string
    {
        $response = $this->handleRequest(
            '/forum/talk/a-topic-' . $this->topic . '/' . ($page > 1 ? '?page=' . $page : ''),
            cookies: $this->actingAs($as)
        );
        self::assertSame(200, $response->getStatusCode());

        return (string) $response->getContent();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function post(string $text, array $attributes = []): int
    {
        $id = Fixture::insert('forum_messages', $attributes + [
            'topic_id'  => $this->topic,
            'user_id'   => $this->author,
            'user_name' => 'Author',
            'date'      => time(),
            'text'      => $text,
        ]);
        $this->container()->get(ForumTopicStatsRecalculator::class)->recalculate($this->topic);

        return $id;
    }
}
