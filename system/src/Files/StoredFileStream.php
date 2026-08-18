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

/**
 * An open stored file, on its way to a visitor.
 *
 * A stream rather than the contents: a controller serving an attachment must not have to hold
 * the whole file in memory, and on a disk that is not on this server there is no path to hand
 * to a file response either.
 */
final readonly class StoredFileStream
{
    /**
     * @param resource $stream Readable stream of the file; the caller closes it.
     */
    public function __construct(
        public mixed $stream,
        public string $name,
        public string $mimeType,
        public int $size,
    ) {
    }
}
