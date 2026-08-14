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

/**
 * Allows what the visitor's roles grant. The ordinary answer, and the only one that says yes on
 * its own — everything else in the chain exists to take something away.
 */
final class RolePermissionVoter implements AccessVoterInterface
{
    public function supports(string $permission, mixed $subject): bool
    {
        return true;
    }

    public function vote(Identity $identity, string $permission, mixed $subject): Vote
    {
        return $identity->hasPermission($permission) ? Vote::Allow : Vote::Abstain;
    }
}
