<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Router;

use Johncms\System\Users\User;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\RouteCollection as SymfonyRouteCollection;

class RouteCollectorFactory
{
    public function __invoke(ContainerInterface $container): SymfonyRouteCollection
    {
        /** @var User $user */
        $user = $container->get(User::class);

        $router = new RouteCollection(new RouteRequirements());
        $this->addRoutesFromConfig($router, $user);
        $this->addModuleRoutes($router, $user);

        return $router->compile();
    }

    private function addRoutesFromConfig(RouteCollection $router, User $user): void
    {
        $registerRoutes = require CONFIG_PATH . 'routes.php';
        $registerRoutes($router, $user);
    }

    private function addModuleRoutes(RouteCollection $router, User $user): void
    {
        foreach (glob(MODULES_PATH . '*/config/routes.php') as $file) {
            $registerRoutes = require $file;
            $registerRoutes($router, $user);
        }
    }
}
