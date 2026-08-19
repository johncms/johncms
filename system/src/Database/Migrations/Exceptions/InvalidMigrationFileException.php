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
 * A file in a migrations directory that cannot be a migration: a name the version cannot be read
 * from, a file returning something other than a Migration, or two files claiming one version.
 *
 * Always fatal, never skipped. A migration silently ignored is a schema change that never happens
 * and a site that fails somewhere else entirely.
 */
final class InvalidMigrationFileException extends RuntimeException
{
}
