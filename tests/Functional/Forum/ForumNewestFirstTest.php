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
 * The setting "newest first" (upfp) of a member turns the topic over: the newest post on top, the
 * reply form above the posts, and a post sent opens the first page, where it is.
 */
final class ForumNewestFirstTest extends FunctionalTestCase
{
    private int $topic;
    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $author = FunctionalUserFactory::create(['name' => 'Author']);
        $this->member = FunctionalUserFactory::create(['name' => 'Member']);
        $section = Fixture::insert('forum_sections', ['name' => 'Talk', 'slug' => 'talk', 'parent' => 0, 'description' => '', 'section_type' => 1]);
        $this->topic = Fixture::insert('forum_topic', ['section_id' => $section, 'name' => 'A topic', 'slug' => 'a-topic', 'user_id' => $author->id, 'user_name' => 'Author', 'deleted' => 0, 'closed' => 0, 'pinned' => 0]);
        foreach (['Oldest post', 'Middle post', 'Newest post'] as $age => $text) {
            Fixture::insert('forum_messages', ['topic_id' => $this->topic, 'user_id' => $author->id, 'user_name' => 'Author', 'date' => time() - 3600 + $age, 'text' => '<p>' . $text . '</p>']);
        }
        $this->container()->get(ForumTopicStatsRecalculator::class)->recalculate($this->topic);
    }

    public function testTheOldestComeFirstAndTheFormUnderThemByDefault(): void
    {
        $page = $this->topicPage();

        self::assertLessThan(strpos($page, 'Newest post'), strpos($page, 'Oldest post'));
        self::assertGreaterThan(strpos($page, 'Newest post'), strpos($page, 'id="topic-reply"'));
    }

    public function testTheNewestComeFirstAndTheFormAboveThemWhenAsked(): void
    {
        $this->setNewestFirst();

        $page = $this->topicPage();

        self::assertLessThan(strpos($page, 'Oldest post'), strpos($page, 'Newest post'));
        self::assertLessThan(strpos($page, 'Newest post'), strpos($page, 'id="topic-reply"'));
    }

    public function testAPostSentOpensThePageItIsOn(): void
    {
        // Two posts on a page: the fourth post is on the second page oldest first, on the first newest first.
        Capsule::table('users')->where('id', $this->member->id)->update(['set_user' => serialize(['kmess' => 2])]);
        $topicUrl = '/forum/talk/a-topic-' . $this->topic . '/';

        self::assertSame($topicUrl . '?page=2', $this->send('<p>A fourth post</p>')->headers->get('Location'));

        $this->setNewestFirst();
        Capsule::table('users')->where('id', $this->member->id)->update(['lastpost' => 0]);
        self::assertSame($topicUrl, $this->send('<p>A fifth post, newest first</p>')->headers->get('Location'));
    }

    private function setNewestFirst(): void
    {
        Capsule::table('users')->where('id', $this->member->id)->update(['set_forum' => serialize(['upfp' => 1])]);
    }

    private function send(string $text): Response
    {
        return $this->postForm('/forum/new-message/' . $this->topic . '/', [
            'msg'    => $text,
            'token'  => $this->container()->get(ForumFormTokens::class)->issue(),
            'submit' => '1',
        ], $this->member);
    }

    private function topicPage(): string
    {
        $response = $this->handleRequest('/forum/talk/a-topic-' . $this->topic . '/', cookies: $this->actingAs($this->member));
        self::assertSame(200, $response->getStatusCode());

        return (string) $response->getContent();
    }
}
