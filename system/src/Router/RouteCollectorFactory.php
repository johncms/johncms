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

    /**
     * Every route a module declares is stamped with the name of that module, taken from the path
     * of the file declaring it. That is what lets the request pipeline set up the module context
     * of the page, instead of each controller naming its own module.
     */
    private function addModuleRoutes(RouteCollection $router, User $user): void
    {
        foreach (glob(MODULES_PATH . '*/config/routes.php') as $file) {
            $router->setModule(basename(dirname($file, 2)));

            $registerRoutes = require $file;
            $registerRoutes($router, $user);
        }

        $router->setModule(null);
    }
}
