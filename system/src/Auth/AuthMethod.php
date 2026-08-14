<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth;

/**
 * How the visitor of the current request was identified.
 *
 * Impersonation is deliberately not a case of its own: an administrator browsing as somebody
 * else still arrives with a session cookie, and what makes the request an impersonation is the
 * impersonator recorded on the identity, not a different way of authenticating. Keeping it out
 * of here means a check like "was this a cookie request" stays true in both cases.
 */
enum AuthMethod: string
{
    case Guest = 'guest';
    case Session = 'session';
    case Token = 'token';
}
