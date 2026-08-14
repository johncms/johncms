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

/**
 * What a voter has to say about one permission check.
 *
 * Deny wins over Allow, and Abstain says nothing at all. That asymmetry is the point of having
 * votes rather than booleans: a ban, an impersonation limit or the abilities of an API token
 * have to be able to take away what a role grants, including from an administrator.
 */
enum Vote
{
    case Allow;
    case Deny;
    case Abstain;
}
