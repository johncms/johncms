<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms;

use Intervention\Image\ImageManager;

class ImageManagerFactory
{
    public function __invoke(): ImageManager
    {
        return new ImageManager(['driver' => (extension_loaded('imagick') ? 'imagick' : 'gd')]);
    }
}
