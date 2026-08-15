<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Forum;

use Johncms\Auth\Authorization\Vote;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\TopicCuratorVoter;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Support\IdentityFactory;

final class TopicCuratorVoterTest extends TestCase
{
    private TopicCuratorVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new TopicCuratorVoter();
    }

    /**
     * The whole point of the subject: the same permission a moderator holds, narrowed down to the
     * one topic somebody was appointed to.
     */
    public function testACuratorModeratesTheTopicTheyWereAppointedTo(): void
    {
        $topic = $this->topicCuratedBy([7 => 'Curator']);

        self::assertSame(
            Vote::Allow,
            $this->voter->vote(IdentityFactory::user(id: 7), ForumPermissions::TOPIC_MODERATE, $topic)
        );
    }

    public function testACuratorOfAnotherTopicGetsNothingHere(): void
    {
        $topic = $this->topicCuratedBy([8 => 'Somebody else']);

        self::assertSame(
            Vote::Abstain,
            $this->voter->vote(IdentityFactory::user(id: 7), ForumPermissions::TOPIC_MODERATE, $topic)
        );
    }

    public function testWithoutATopicThereIsNothingToSayAbout(): void
    {
        self::assertFalse($this->voter->supports(ForumPermissions::TOPIC_MODERATE, null));
        self::assertFalse(
            $this->voter->supports(ForumPermissions::TOPIC_DESTROY, $this->topicCuratedBy([7 => 'Curator']))
        );
    }

    /**
     * @param array<int, string> $curators
     */
    private function topicCuratedBy(array $curators): ForumTopic
    {
        // Built without its constructor: the model resolves the current user and a date formatter
        // from the container there, and the voter reads nothing but the curators.
        $topic = (new ReflectionClass(ForumTopic::class))->newInstanceWithoutConstructor();
        $topic->setRawAttributes(['id' => 1, 'curators' => serialize($curators)]);

        return $topic;
    }
}
