<?php

/**
 * This file is part of mobiCMS Content Management System.
 *
 * @copyright   Oleg Kasyanov <dev@mobicms.net>
 * @license     https://opensource.org/licenses/GPL-3.0 GPL-3.0 (see the LICENSE.md file)
 * @link        http://mobicms.org mobiCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Container;

use Laminas\ServiceManager\ServiceManager;

class Factory
{
    /** @var null|ServiceManager */
    private static $containerInstance;

    public static function getContainer(): ServiceManager
    {
        if (null === self::$containerInstance) {
            // Build configuration
            $config = (new Config())();
            $dependencies = $config['dependencies'];
            $dependencies['services']['database'] = $config['pdo'] ?? [];
            unset($config['dependencies'], $config['pdo']);
            $dependencies['services']['config'] = $config;

            // Build container
            self::$containerInstance = new ServiceManager($dependencies);
        }

        return self::$containerInstance;
    }
}
