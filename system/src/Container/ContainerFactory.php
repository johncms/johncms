<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Container;

use Illuminate\Container\Container;
use Johncms\Config;
use Psr\Container\ContainerInterface;

class ContainerFactory
{
    private static bool $configured = false;

    public static function getContainer(): Container
    {
        $container = Container::getInstance();
        if (! self::$configured) {
            // Build configuration
            $config = (new Config())();
            $dependencies = $config['dependencies'];
            $container->instance('config', $config);
            $container->bind(ContainerInterface::class, fn() => $container, true);
            $container->bind(\Illuminate\Contracts\Container\Container::class, fn() => $container, true);

            // Registering of factories
            $factories = $dependencies['factories'] ?? [];
            foreach ($factories as $abstract => $concrete) {
                $container->bind(
                    $abstract,
                    /** @psalm-return mixed */
                    fn(ContainerInterface $container) => (new $concrete($container))($container),
                    true
                );
            }

            // Shared classes
            $shared = $dependencies['shared'] ?? [];
            foreach ($shared as $abstract => $concrete) {
                $container->bind($abstract, $concrete, true);
            }

            // Registering aliases
            $aliases = $dependencies['aliases'] ?? [];
            foreach ($aliases as $alias => $concrete) {
                $container->alias($concrete, $alias);
            }

            self::$configured = true;
        }

        return $container;
    }
}
