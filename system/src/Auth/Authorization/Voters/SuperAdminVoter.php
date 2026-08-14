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
use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Auth\Authorization\Vote;
use Johncms\Auth\Identity;

/**
 * Allows everything to the roles at supervisor level.
 *
 * The safety net: with permissions editable from the admin panel, a wrong click on the wrong
 * matrix would otherwise be enough to lock a site out of its own settings for good. This is what
 * guarantees there is always a way back in.
 *
 * A Deny elsewhere in the chain still wins — a ban and the abilities of an API token cut down a
 * supervisor like anybody else.
 */
final readonly class SuperAdminVoter implements AccessVoterInterface
{
    public function __construct(private RoleRepositoryInterface $roles)
    {
    }

    public function supports(string $permission, mixed $subject): bool
    {
        return true;
    }

    public function vote(Identity $identity, string $permission, mixed $subject): Vote
    {
        if ($identity->isGuest()) {
            return Vote::Abstain;
        }

        foreach ($identity->roles as $slug) {
            if ($this->levelOf($slug) >= SystemRole::SUPERVISOR_LEVEL) {
                return Vote::Allow;
            }
        }

        return Vote::Abstain;
    }

    /**
     * The built-in roles answer without a query; one the site added is looked up.
     */
    private function levelOf(string $slug): int
    {
        $systemRole = SystemRole::tryFrom($slug);

        if ($systemRole !== null) {
            return $systemRole->level();
        }

        $role = $this->roles->findBySlug($slug);

        return $role === null ? 0 : $role->level;
    }
}
