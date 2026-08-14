<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Session;

/**
 * Why a session stopped working. Recorded rather than inferred: "signed out" and "an
 * administrator closed it" look identical on the row otherwise, and the difference is exactly
 * what somebody looking into an account later needs.
 */
enum SessionRevocationReason: string
{
    case Logout = 'logout';
    case PasswordChange = 'password_change';
    case Admin = 'admin';
    case ImpersonationStop = 'impersonation_stop';
}
