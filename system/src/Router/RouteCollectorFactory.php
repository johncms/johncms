<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Router;

use FastRoute\RouteCollector;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteParser\Std as RouteParser;
use Johncms\Api\UserInterface;
use Psr\Container\ContainerInterface;

class RouteCollectorFactory
{
    public function __invoke(ContainerInterface $container) : RouteCollector
    {
        /** @var UserInterface $user */
        $user = $container->get(UserInterface::class);
        $router = new RouteCollector(new RouteParser(), new GroupCountBased());
        $this->addRoutesFromConfig($router, $user);

        return $router;
    }

    private function addRoutesFromConfig(RouteCollector $map, UserInterface $user) : void
    {
        (require CONFIG_PATH . 'routes.php')($map, $user);
    }
}
