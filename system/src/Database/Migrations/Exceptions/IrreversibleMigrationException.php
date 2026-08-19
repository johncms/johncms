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
 * A migration asked to roll back that never said how. Not a defect: most steps forward cannot be
 * undone without losing what they carried, and a migration that says nothing is refusing rather
 * than forgetting.
 */
final class IrreversibleMigrationException extends RuntimeException
{
}
