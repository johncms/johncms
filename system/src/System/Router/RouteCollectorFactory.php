<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Router;

use FastRoute\RouteCollector;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteParser\Std as RouteParser;
use Johncms\System\Users\User;
use Psr\Container\ContainerInterface;

class RouteCollectorFactory
{
    public function __invoke(ContainerInterface $container): RouteCollector
    {
        /** @var User $user */
        $user = $container->get(User::class);
        $router = new RouteCollector(new RouteParser(), new GroupCountBased());
        $this->addRoutesFromConfig($router, $user);

        return $router;
    }

    private function addRoutesFromConfig(RouteCollector $map, User $user): void
    {
        (require CONFIG_PATH . 'routes.php')($map, $user);
    }
}
