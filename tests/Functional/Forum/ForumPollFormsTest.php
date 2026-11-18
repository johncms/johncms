<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * A poll is added and changed by forms that come back with what was wrong beside the fields and a
 * 422; saved, they return to the topic. So do the curators of a topic.
 */
final class ForumPollFormsTest extends FunctionalTestCase
{
    private int $topic;
    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->moderator = FunctionalUserFactory::createSupervisor();
        $section = Fixture::insert('forum_sections', ['name' => 'Talk', 'slug' => 'talk', 'parent' => 0, 'description' => '', 'section_type' => 1]);
        $this->topic = Fixture::insert('forum_topic', [
            'section_id' => $section,
            'name'       => 'A topic',
            'slug'       => 'a-topic',
            'user_id'    => $this->moderator->id,
            'user_name'  => 'Moderator',
            'deleted'    => 0,
            'closed'     => 0,
            'pinned'     => 0,
        ]);
    }

    public function testAPollWithoutAQuestionOrTwoAnswersComesBack(): void
    {
        // The answers are the fields 0, 1, 2… of the form.
        /** @var array<string, mixed> $form */
        $form = ['count_vote' => '2', 'name_vote' => '', '0' => 'Yes', 'submit' => '1'];
        $response = $this->postForm('/forum/addvote/' . $this->topic . '/', $form, $this->moderator);

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('value="Yes"', (string) $response->getContent(), 'The answer typed stays.');
        self::assertSame(0, Capsule::table('cms_forum_vote')->count());
    }

    public function testAPollIsAddedAndTheTopicOpensAgain(): void
    {
        /** @var array<string, mixed> $form */
        $form = ['count_vote' => '3', 'name_vote' => 'Tea or coffee?', '0' => 'Tea', '1' => '', '2' => 'Coffee', 'submit' => '1'];
        $response = $this->postForm('/forum/addvote/' . $this->topic . '/', $form, $this->moderator);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/forum/talk/a-topic-' . $this->topic . '/', $response->headers->get('Location'));
        self::assertSame(['Tea', 'Coffee'], Capsule::table('cms_forum_vote')->where('type', 2)->orderBy('id')->pluck('name')->all(), 'The empty field is left out.');
    }

    public function testASavedAnswerCannotBeEmptied(): void
    {
        Fixture::insert('cms_forum_vote', ['type' => 1, 'topic' => $this->topic, 'name' => 'Tea or coffee?', 'time' => time()]);
        $tea = Fixture::insert('cms_forum_vote', ['type' => 2, 'topic' => $this->topic, 'name' => 'Tea']);
        $coffee = Fixture::insert('cms_forum_vote', ['type' => 2, 'topic' => $this->topic, 'name' => 'Coffee']);

        $refused = $this->postForm('/forum/editvote/' . $this->topic . '/', ['count_vote' => '2', 'name_vote' => 'Tea or coffee?', $tea . 'vote' => '', $coffee . 'vote' => 'Coffee', 'submit' => '1'], $this->moderator);
        self::assertSame(422, $refused->getStatusCode());
        self::assertSame('Tea', Capsule::table('cms_forum_vote')->where('id', $tea)->value('name'));

        $saved = $this->postForm('/forum/editvote/' . $this->topic . '/', ['count_vote' => '2', 'name_vote' => 'Which?', $tea . 'vote' => 'Green tea', $coffee . 'vote' => 'Coffee', 'submit' => '1'], $this->moderator);
        self::assertSame(302, $saved->getStatusCode());
        self::assertSame('Green tea', Capsule::table('cms_forum_vote')->where('id', $tea)->value('name'));
    }

    public function testThePollIsManagedByItsOwnPermission(): void
    {
        $pollManager = FunctionalUserFactory::createWithPermissions(['forum.view', 'forum.post', 'forum.poll.manage']);
        $topicModerator = FunctionalUserFactory::createWithPermissions(['forum.view', 'forum.post', 'forum.topic.moderate']);

        self::assertSame(200, $this->handleRequest('/forum/addvote/' . $this->topic . '/', cookies: $this->actingAs($pollManager))->getStatusCode());
        self::assertSame(403, $this->handleRequest('/forum/addvote/' . $this->topic . '/', cookies: $this->actingAs($topicModerator))->getStatusCode());

        $page = (string) $this->handleRequest('/forum/talk/a-topic-' . $this->topic . '/', cookies: $this->actingAs($pollManager))->getContent();
        self::assertStringContainsString('href="/forum/addvote/' . $this->topic . '/"', $page, 'The menu offers the poll.');
        self::assertStringNotContainsString('form="topic-close"', $page, 'Not what the poll manager may not do.');
    }

    public function testTheCuratorsAreSavedAndTheTopicOpensAgain(): void
    {
        $writer = FunctionalUserFactory::create(['name' => 'Writer']);
        Fixture::insert('forum_messages', ['topic_id' => $this->topic, 'user_id' => $writer->id, 'user_name' => 'Writer', 'date' => time(), 'text' => 'Hello']);

        $response = $this->postForm('/forum/curators/' . $this->topic . '/', ['users' => [$writer->id => 'Writer'], 'submit' => '1'], $this->moderator);

        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('Writer', (string) Capsule::table('forum_topic')->where('id', $this->topic)->value('curators'));
    }
}
