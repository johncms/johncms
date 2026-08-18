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
 * A stored file as the rest of the CMS sees it.
 *
 * What FileStore hands out instead of the model: the four facts a module actually uses, and no
 * way to change the row or the file behind them. The URL is filled in by whoever knows the
 * disk, which is why a module never has to build one out of a path.
 */
final readonly class StoredFileDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public int $size,
        /** Empty when the file is on a disk that is not public: it is served by a controller then. */
        public string $url,
    ) {
    }
}
