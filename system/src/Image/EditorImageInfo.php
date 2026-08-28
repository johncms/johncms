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

/**
 * What an uploaded picture turned out to be, read from its contents rather than from its name.
 */
final readonly class EditorImageInfo
{
    public function __construct(
        public int $width,
        public int $height,
        /** One of the IMAGETYPE_* constants. */
        public int $type,
    ) {
    }
}
