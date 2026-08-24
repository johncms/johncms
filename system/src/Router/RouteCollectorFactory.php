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

use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleRegistryFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\RouteCollection as SymfonyRouteCollection;

class RouteCollectorFactory
{
    public function __construct(private readonly ?ModuleRegistry $registry = null)
    {
    }

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
     * The routes of the modules the registry loads, and of no others: a module that is switched
     * off answers nothing, rather than answering with pages whose templates and services are no
     * longer registered.
     *
     * Every route is stamped with the key of its module. That is what lets the request pipeline
     * set up the module context of the page, instead of each controller naming its own module.
     */
    private function addModuleRoutes(RouteCollection $router): void
    {
        $registry = $this->registry ?? ModuleRegistryFactory::registry();

        foreach ($registry->enabled() as $key => $manifest) {
            $file = $manifest->path . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.php';

            if (! is_file($file)) {
                continue;
            }

            $router->setModule($key);

            $registerRoutes = require $file;
            $registerRoutes($router);
        }

        $router->setModule(null);
    }
}
