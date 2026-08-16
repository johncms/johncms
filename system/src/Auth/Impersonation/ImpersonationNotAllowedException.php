<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Impersonation;

use RuntimeException;

/**
 * Browsing as this account is refused: no permission for it, the account outranks the visitor,
 * it does not exist, or it is the visitor's own.
 */
final class ImpersonationNotAllowedException extends RuntimeException
{
}
