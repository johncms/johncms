<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\External;

/**
 * The identity belongs with an account that already exists, but linking it automatically would
 * be an account takeover: the address is not vouched for by both sides.
 *
 * The answer is never "sign them in anyway" — it is "sign in with your password first, then
 * link".
 */
final class ExternalAccountConflictException extends ExternalAuthException
{
}
