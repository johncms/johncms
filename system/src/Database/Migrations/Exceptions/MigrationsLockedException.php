<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Migrations\Exceptions;

use RuntimeException;

/**
 * Another run holds the lock. Two migrators against one database would apply the same step twice
 * or interleave two halves of the schema.
 */
final class MigrationsLockedException extends RuntimeException
{
}
