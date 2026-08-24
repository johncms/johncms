<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Exceptions;

use RuntimeException;

/**
 * A module.php that cannot be read as a manifest: it returns something other than an array, its
 * key is malformed or names a place other than where the file lies, or a field carries a type
 * nothing can be done with.
 *
 * Fatal rather than skipped. A module quietly dropped because of a typo in its manifest is a set
 * of routes, tables and permissions that silently stop existing, and the failure shows up
 * somewhere else entirely.
 */
final class InvalidModuleManifestException extends RuntimeException
{
}
