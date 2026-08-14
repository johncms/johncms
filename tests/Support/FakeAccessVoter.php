<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Auth\Authorization\AccessVoterInterface;
use Johncms\Auth\Authorization\Vote;
use Johncms\Auth\Identity;

/**
 * A voter that returns what the test tells it to and records whether it was asked.
 */
final class FakeAccessVoter implements AccessVoterInterface
{
    public bool $voted = false;

    public mixed $seenSubject = null;

    public function __construct(
        private readonly Vote $vote,
        private readonly bool $supports = true,
    ) {
    }

    public function supports(string $permission, mixed $subject): bool
    {
        return $this->supports;
    }

    public function vote(Identity $identity, string $permission, mixed $subject): Vote
    {
        $this->voted = true;
        $this->seenSubject = $subject;

        return $this->vote;
    }
}
