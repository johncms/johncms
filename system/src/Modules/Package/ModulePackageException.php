<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Package;

use RuntimeException;

/**
 * An archive that is not a module package, or one that is not safe to unpack.
 *
 * The message goes straight to whoever is installing it, so it says what is wrong with the file
 * rather than what failed inside.
 */
final class ModulePackageException extends RuntimeException
{
}
