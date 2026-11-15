<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Models\ForumVote;
use Johncms\Modules\Forum\Domain\Models\ForumVoteUser;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * What changes a topic is done by a form only: opening the address of an action changes nothing,
 * and removing the poll or an answer of it also waits for the answer of the dialog.
 */
final class ForumTopicActionsTest extends FunctionalTestCase
{
    private int $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = Fixture::insert('forum_sections', ['name' => 'Talk', 'slug' => 'talk', 'parent' => 0, 'description' => '', 'section_type' => 1]);
    }

    public function testATopicIsClosedAndOpenedOnlyByAForm(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        $topic = $this->topic();

        $this->handleRequest('/forum/close/' . $topic . '/?closed', cookies: $this->actingAs($moderator));
        self::assertFalse($this->fresh($topic)->closed, 'Opening the address changes nothing.');

        self::assertSame(302, $this->postForm('/forum/close/' . $topic . '/', ['closed' => '1'], $moderator)->getStatusCode());
        self::assertTrue($this->fresh($topic)->closed);

        $this->postForm('/forum/close/' . $topic . '/', ['closed' => '0'], $moderator);
        self::assertFalse($this->fresh($topic)->closed);
    }

    public function testATopicIsPinnedAndUnpinnedOnlyByAForm(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        $topic = $this->topic();

        $this->handleRequest('/forum/pin-topic/' . $topic . '/?pin', cookies: $this->actingAs($moderator));
        self::assertFalse($this->fresh($topic)->pinned, 'Opening the address changes nothing.');

        $this->postForm('/forum/pin-topic/' . $topic . '/', ['pin' => '1'], $moderator);
        self::assertTrue($this->fresh($topic)->pinned);

        $this->postForm('/forum/pin-topic/' . $topic . '/', ['pin' => '0'], $moderator);
        self::assertFalse($this->fresh($topic)->pinned);
    }

    public function testAHiddenTopicIsRestoredOnlyByAForm(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        $topic = $this->topic(['deleted' => 1, 'deleted_by' => 'Alex']);

        $this->handleRequest('/forum/restore-topic/' . $topic . '/', cookies: $this->actingAs($moderator));
        self::assertTrue($this->fresh($topic)->deleted, 'Opening the address changes nothing.');

        $this->postForm('/forum/restore-topic/' . $topic . '/', [], $moderator);
        self::assertFalse($this->fresh($topic)->deleted);
    }

    public function testAMemberMayNotCloseATopic(): void
    {
        $topic = $this->topic();

        $this->postForm('/forum/close/' . $topic . '/', ['closed' => '1'], FunctionalUserFactory::create());

        self::assertFalse($this->fresh($topic)->closed);
    }

    public function testThePollIsDeletedOnlyByAConfirmedForm(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        $topic = $this->topic(['has_poll' => 1]);
        $this->poll($topic, ['Yes', 'No']);

        $this->handleRequest('/forum/delvote/' . $topic . '/?yes', cookies: $this->actingAs($moderator));
        self::assertTrue($this->hasPoll($topic), 'Opening the address deletes nothing.');

        self::assertSame(409, $this->postForm('/forum/delvote/' . $topic . '/', [], $moderator)->getStatusCode());
        self::assertTrue($this->hasPoll($topic));

        $deleted = $this->postForm('/forum/delvote/' . $topic . '/', ['confirmed' => '1'], $moderator);
        self::assertSame(302, $deleted->getStatusCode());
        self::assertSame('/forum/talk/a-topic-' . $topic . '/', $deleted->headers->get('Location'));
        self::assertFalse($this->hasPoll($topic));
    }

    public function testAnAnswerIsDeletedOnlyByAConfirmedForm(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        $topic = $this->topic(['has_poll' => 1]);
        $answers = $this->poll($topic, ['Yes', 'No', 'Maybe']);
        $address = '/forum/editvote/' . $topic . '/answers/' . $answers[2] . '/delete';

        $this->handleRequest('/forum/editvote/' . $topic . '/?vote=' . $answers[2] . '&delvote&yes', cookies: $this->actingAs($moderator));
        self::assertTrue(ForumVote::query()->whereKey($answers[2])->exists(), 'Opening the address deletes nothing.');

        self::assertSame(409, $this->postForm($address, [], $moderator)->getStatusCode());
        self::assertTrue(ForumVote::query()->whereKey($answers[2])->exists());

        $deleted = $this->postForm($address, ['confirmed' => '1'], $moderator);
        self::assertSame('/forum/editvote/' . $topic . '/', $deleted->headers->get('Location'));
        self::assertFalse(ForumVote::query()->whereKey($answers[2])->exists());

        $form = $this->handleRequest('/forum/editvote/' . $topic . '/', cookies: $this->actingAs($moderator));
        self::assertSame(200, $form->getStatusCode(), 'The form of the poll still opens.');
    }

    public function testAVoteIsCountedByAFormAndLeadsBackToTheTopic(): void
    {
        $member = FunctionalUserFactory::create();
        $topic = $this->topic(['has_poll' => 1]);
        $answers = $this->poll($topic, ['Yes', 'No']);

        $this->handleRequest('/forum/poll-vote/' . $topic . '/?vote=' . $answers[0], cookies: $this->actingAs($member));
        self::assertFalse(ForumVoteUser::query()->where('topic', $topic)->exists(), 'Opening the address counts nothing.');

        $voted = $this->postForm('/forum/poll-vote/' . $topic . '/', ['vote' => (string) $answers[0]], $member);

        self::assertSame(302, $voted->getStatusCode());
        self::assertSame('/forum/talk/a-topic-' . $topic . '/', $voted->headers->get('Location'));
        self::assertTrue(ForumVoteUser::query()->where('topic', $topic)->where('user', $member->id)->exists());
    }

    public function testANewTopicIsStartedFromALink(): void
    {
        $member = FunctionalUserFactory::create();

        $section = (string) $this->handleRequest('/forum/talk/', cookies: $this->actingAs($member))->getContent();
        self::assertStringContainsString('<a href="/forum/new-topic/' . $this->section . '/" class="btn btn-primary">', $section);

        $form = $this->handleRequest('/forum/new-topic/' . $this->section . '/', cookies: $this->actingAs($member));
        self::assertSame(200, $form->getStatusCode());
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function topic(array $attributes = []): int
    {
        return Fixture::insert('forum_topic', $attributes + [
            'section_id' => $this->section,
            'name'       => 'A topic',
            'slug'       => 'a-topic',
        ]);
    }

    /**
     * @param list<string> $answers
     * @return list<int> The ids of the answers.
     */
    private function poll(int $topic, array $answers): array
    {
        Fixture::insert('cms_forum_vote', ['type' => 1, 'topic' => $topic, 'name' => 'A poll', 'time' => time()]);

        return array_map(
            static fn(string $answer): int => Fixture::insert('cms_forum_vote', ['type' => 2, 'topic' => $topic, 'name' => $answer]),
            $answers,
        );
    }

    private function hasPoll(int $topic): bool
    {
        return ForumVote::query()->where('topic', $topic)->where('type', 1)->exists();
    }

    private function fresh(int $topic): ForumTopic
    {
        /** @var ForumTopic */
        return ForumTopic::query()->withoutGlobalScopes()->findOrFail($topic);
    }
}
