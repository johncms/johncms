<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Schema;

use RuntimeException;

/**
 * A table description that cannot be turned into DDL — an incomplete foreign key, a key over no
 * columns. Thrown while the description is being applied, before anything reaches the database.
 */
final class SchemaDefinitionException extends RuntimeException
{
}
