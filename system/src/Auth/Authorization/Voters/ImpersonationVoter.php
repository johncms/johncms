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
use Johncms\Auth\Authorization\PermissionMatcher;
use Johncms\Auth\Authorization\Vote;
use Johncms\Auth\Identity;
use Johncms\Auth\Impersonation\ImpersonationSettings;

/**
 * Refuses the dangerous half of what an account may do while somebody is browsing as it.
 *
 * A Deny, not a missing Allow: the point is to outrank the roles of the account being browsed as
 * and those of the administrator alike. Impersonation exists to reproduce what a user sees, so
 * entering the panel or handing out roles through it serves no purpose beyond obscuring who acted.
 *
 * The list is configuration (`auth.impersonation.denied_permissions`), because what counts as
 * dangerous differs between sites; the shipped default is the strict one.
 */
final readonly class ImpersonationVoter implements AccessVoterInterface
{
    public function __construct(private ImpersonationSettings $settings)
    {
    }

    public function supports(string $permission, mixed $subject): bool
    {
        return true;
    }

    public function vote(Identity $identity, string $permission, mixed $subject): Vote
    {
        if (! $identity->isImpersonating()) {
            return Vote::Abstain;
        }

        return PermissionMatcher::matchesAny($this->settings->deniedPermissions, $permission)
            ? Vote::Deny
            : Vote::Abstain;
    }
}
