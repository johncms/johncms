<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Files;

use RuntimeException;

/**
 * A file could not be stored: the disk refused it, or its row could not be written.
 *
 * Either way nothing was left behind — a file whose registration failed is removed again, so a
 * caller catching this knows the store is as it was before the call.
 */
final class FileStoreException extends RuntimeException
{
}
