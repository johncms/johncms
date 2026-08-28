<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Image;

use RuntimeException;

/**
 * An upload the editor may not store: the wrong kind of file, one that is too heavy, or a
 * picture the processor could not read.
 *
 * The message is meant for the visitor — the editor shows it in place of the picture — so it is
 * translated where it is thrown.
 */
final class EditorImageUploadException extends RuntimeException
{
}
