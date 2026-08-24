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

use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\RouteCollection as SymfonyRouteCollection;

class RouteCollectorFactory
{
    /**
     * The collection does not depend on who is asking: a route closed to the visitor is declared
     * all the same and its gate is a middleware. That is what makes the collection dumpable and
     * cacheable, and what lets the routing happen without identifying the visitor first.
     */
    public function __invoke(ContainerInterface $container): SymfonyRouteCollection
    {
        $router = new RouteCollection(new RouteRequirements());
        $this->addRoutesFromConfig($router);
        $this->addModuleRoutes($router);

        return $router->compile();
    }

    private function addRoutesFromConfig(RouteCollection $router): void
    {
        $registerRoutes = require CONFIG_PATH . 'routes.php';
        $registerRoutes($router);
    }

    /**
     * Every route a module declares is stamped with the key of that module — vendor and name,
     * taken from the path of the file declaring it. That is what lets the request pipeline set up
     * the module context of the page, instead of each controller naming its own module.
     */
    private function addModuleRoutes(RouteCollection $router): void
    {
        foreach (glob(MODULES_PATH . '*/*/config/routes.php') as $file) {
            $moduleDirectory = dirname($file, 2);
            $router->setModule(basename(dirname($moduleDirectory)) . '/' . basename($moduleDirectory));

            $registerRoutes = require $file;
            $registerRoutes($router);
        }

        $router->setModule(null);
    }
}
