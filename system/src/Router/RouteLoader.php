<?php

declare(strict_types=1);

namespace Johncms\Router;

class RouteLoader
{
    public function load(): RouteCollection
    {
        $routeCollection = new RouteCollection();
        $routerConfigs = glob(MODULES_PATH . '*/*/config/routes.php');
        foreach ($routerConfigs as $routerConfig) {
            (require $routerConfig)($routeCollection);
        }

        return $routeCollection;
    }
}
