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
 * A migration was asked for in a source nobody provides — a mistyped module name, most likely.
 */
final class UnknownMigrationSourceException extends RuntimeException
{
}
