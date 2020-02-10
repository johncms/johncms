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

use Psr\Container\ContainerInterface;

class ImageManager
{
    public function __invoke(ContainerInterface $container)
    {
        $config = [
            'driver' => extension_loaded('imagick') ? 'imagick' : 'gd',
        ];
        return new \Intervention\Image\ImageManager($config);
    }
}
