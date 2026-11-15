<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Moving and changing a topic: the topic page asks in a dialog, and the pages of the two forms do
 * the same without the script. The dialog sends the same fields, so the pages are what is tested.
 */
final class ForumTopicManagementTest extends FunctionalTestCase
{
    private int $talk;
    private int $news;
    private int $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Fixture::insert('forum_sections', ['name' => 'Community', 'slug' => 'community', 'parent' => 0, 'description' => '', 'section_type' => 0]);
        $other = Fixture::insert('forum_sections', ['name' => 'Projects', 'slug' => 'projects', 'parent' => 0, 'description' => '', 'section_type' => 0]);
        $this->talk = Fixture::insert('forum_sections', ['name' => 'Talk', 'slug' => 'talk', 'parent' => $category, 'description' => '', 'section_type' => 1]);
        $this->news = Fixture::insert('forum_sections', ['name' => 'News', 'slug' => 'news', 'parent' => $other, 'description' => '', 'section_type' => 1]);
        $this->topic = Fixture::insert('forum_topic', [
            'section_id' => $this->talk,
            'name'       => 'A topic',
            'slug'       => 'a-topic',
            'user_id'    => 1,
            'user_name'  => 'Author',
            'deleted'    => 0,
        ]);
    }

    public function testThePageOfMovingListsTheSectionsOfEveryCategoryButTheCurrentOne(): void
    {
        $page = (string) $this->handleRequest('/forum/move-topic/' . $this->topic . '/', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getContent();

        self::assertStringContainsString('<option value="' . $this->news . '">Projects › News</option>', $page);
        self::assertStringNotContainsString('<option value="' . $this->talk . '">', $page, 'The topic is there already.');
    }

    public function testATopicIsMovedToAnotherCategory(): void
    {
        $moved = $this->postForm('/forum/move-topic/' . $this->topic . '/', ['submit' => '1', 'razd' => (string) $this->news], FunctionalUserFactory::createSupervisor());

        self::assertSame(302, $moved->getStatusCode());
        self::assertSame($this->news, $this->fresh()->section_id);
    }

    public function testAMemberMayNotMoveATopic(): void
    {
        $this->postForm('/forum/move-topic/' . $this->topic . '/', ['submit' => '1', 'razd' => (string) $this->news], FunctionalUserFactory::create());

        self::assertSame($this->talk, $this->fresh()->section_id);
    }

    public function testATopicIsChangedWithItsMetaTags(): void
    {
        $changed = $this->postForm('/forum/change-topic/' . $this->topic . '/', [
            'name'             => 'A better topic',
            'meta_keywords'    => 'forum, topic',
            'meta_description' => 'What the topic is about',
        ], FunctionalUserFactory::createSupervisor());

        self::assertSame(302, $changed->getStatusCode());
        $topic = $this->fresh();
        self::assertSame('A better topic', $topic->name);
        self::assertSame('forum, topic', $topic->meta_keywords);
        self::assertSame('What the topic is about', $topic->meta_description);
    }

    public function testAWrongNameComesBackToThePageOfTheFormWithTheError(): void
    {
        $refused = $this->postForm('/forum/change-topic/' . $this->topic . '/', ['name' => 'A'], FunctionalUserFactory::createSupervisor());

        self::assertSame(200, $refused->getStatusCode());
        self::assertStringContainsString('id="change-topic-page-name"', (string) $refused->getContent());
        self::assertStringContainsString('is-invalid', (string) $refused->getContent());
        self::assertSame('A topic', $this->fresh()->name);
    }

    private function fresh(): ForumTopic
    {
        /** @var ForumTopic */
        return ForumTopic::query()->withoutGlobalScopes()->findOrFail($this->topic);
    }
}
