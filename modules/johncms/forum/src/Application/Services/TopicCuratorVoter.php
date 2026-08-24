<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Auth\Authorization\AccessVoterInterface;
use Johncms\Auth\Authorization\Vote;
use Johncms\Auth\Identity;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;

/**
 * A curator moderates the topic they were appointed to, and nothing else.
 *
 * This is what the second argument of can() was built for: the permission is the same one a
 * moderator holds, and the subject is what narrows it down to one topic. The old code expressed
 * it by pretending the visitor had the number of a forum moderator for the length of a request.
 */
final readonly class TopicCuratorVoter implements AccessVoterInterface
{
    public function supports(string $permission, mixed $subject): bool
    {
        return $permission === ForumPermissions::TOPIC_MODERATE && $subject instanceof ForumTopic;
    }

    public function vote(Identity $identity, string $permission, mixed $subject): Vote
    {
        if ($identity->isGuest() || ! $subject instanceof ForumTopic) {
            return Vote::Abstain;
        }

        return array_key_exists($identity->userId, (array) $subject->curators)
            ? Vote::Allow
            : Vote::Abstain;
    }
}
