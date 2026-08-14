<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authorization\Voters;

use Johncms\Auth\Authorization\AccessVoterInterface;
use Johncms\Auth\Authorization\Vote;
use Johncms\Auth\Identity;
use Johncms\Users\Ban;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Refuses everything to a banned account.
 *
 * A Deny rather than the absence of an Allow, which is the difference that matters: a ban has to
 * outrank whatever the roles say, including for an administrator. The previous code achieved the
 * same by zeroing users.rights in memory while the request was served — an effect that only
 * worked because everything read that one number.
 */
final class BanVoter implements AccessVoterInterface, ResetInterface
{
    /** @var array<int, bool> Answered once per request; the identity does not change within one. */
    private array $banned = [];

    public function supports(string $permission, mixed $subject): bool
    {
        return true;
    }

    public function vote(Identity $identity, string $permission, mixed $subject): Vote
    {
        if ($identity->isGuest()) {
            return Vote::Abstain;
        }

        return $this->isBanned($identity->userId) ? Vote::Deny : Vote::Abstain;
    }

    public function reset(): void
    {
        $this->banned = [];
    }

    private function isBanned(int $userId): bool
    {
        return $this->banned[$userId] ??= Ban::query()
            ->where('user_id', '=', $userId)
            ->where('ban_time', '>', time())
            ->exists();
    }
}
