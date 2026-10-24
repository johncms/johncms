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
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Auth\Authorization\Vote;
use Johncms\Auth\Identity;
use Johncms\Users\Ban;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Leaves an account under a full ban what a guest may do, and nothing more.
 *
 * A Deny rather than the absence of an Allow, which is the difference that matters: a full ban has
 * to outrank whatever the roles say, so a banned moderator moderates nothing. What a guest may do —
 * reading the sections, mostly — stays open, or a banned member would see less of the site than
 * somebody who never signed in.
 *
 * A ban of one section is not this voter's business: the section asks for it where it writes, and
 * the rest of the site stays as it was. The supervisor keeps the way back into a site that
 * SuperAdminVoter guarantees.
 */
final class BanVoter implements AccessVoterInterface, ResetInterface
{
    /** The kind of ban that closes the whole site. */
    private const int FULL_BAN = 1;

    /** @var array<int, bool> Answered once per request; the identity does not change within one. */
    private array $banned = [];

    private ?Identity $guest = null;

    public function __construct(
        private readonly PermissionResolver $permissionResolver,
        private readonly RoleLevels $roleLevels,
    ) {
    }

    public function supports(string $permission, mixed $subject): bool
    {
        return true;
    }

    public function vote(Identity $identity, string $permission, mixed $subject): Vote
    {
        if ($identity->isGuest() || ! $this->isBanned($identity->userId)) {
            return Vote::Abstain;
        }

        if (SystemRole::grantsEverything($this->roleLevels->highest($identity))) {
            return Vote::Abstain;
        }

        return $this->guest()->hasPermission($permission) ? Vote::Abstain : Vote::Deny;
    }

    public function reset(): void
    {
        $this->banned = [];
        $this->guest = null;
    }

    private function isBanned(int $userId): bool
    {
        return $this->banned[$userId] ??= Ban::query()
            ->where('user_id', '=', $userId)
            ->where('ban_type', '=', self::FULL_BAN)
            ->where('ban_time', '>', time())
            ->exists();
    }

    private function guest(): Identity
    {
        return $this->guest ??= $this->permissionResolver->resolve(Identity::guest());
    }
}
