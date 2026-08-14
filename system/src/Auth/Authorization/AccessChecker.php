<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authorization;

use Johncms\Auth\CurrentUser;
use Johncms\Auth\Identity;

/**
 * Collects the votes and applies the only rule there is: any Deny refuses, otherwise a single
 * Allow is enough, and a check nobody voted on is refused.
 *
 * Refusing by default matters more than it looks: a permission whose voter was forgotten, or
 * whose module is switched off, closes the door instead of opening it.
 *
 * Every voter is asked even after an Allow, because a later one may still Deny — that is how a
 * ban outranks a role and how the abilities of an API token cut down an administrator.
 */
final readonly class AccessChecker implements AccessCheckerInterface
{
    /**
     * @param iterable<AccessVoterInterface> $voters
     */
    public function __construct(
        private CurrentUser $currentUser,
        private iterable $voters,
    ) {
    }

    public function allows(string $permission, mixed $subject = null): bool
    {
        return $this->allowsFor($this->currentUser->identity(), $permission, $subject);
    }

    public function allowsFor(Identity $identity, string $permission, mixed $subject = null): bool
    {
        $allowed = false;

        foreach ($this->voters as $voter) {
            if (! $voter->supports($permission, $subject)) {
                continue;
            }

            $vote = $voter->vote($identity, $permission, $subject);

            if ($vote === Vote::Deny) {
                return false;
            }

            if ($vote === Vote::Allow) {
                $allowed = true;
            }
        }

        return $allowed;
    }
}
