<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Storage;

use RuntimeException;

/**
 * Everything a disk cannot do: an unwritable directory, a missing file, a refused connection.
 *
 * The library exception is kept as the previous one, so a log still shows what flysystem said,
 * while callers catch this and only this — a module has no business knowing which library sits
 * behind the disk.
 */
class StorageException extends RuntimeException
{
}
