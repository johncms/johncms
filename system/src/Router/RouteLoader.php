<?php

declare(strict_types=1);

namespace Johncms\Router;

class RouteLoader
{
    public function __construct(
        private RouteCollection $routeCollection
    ) {
    }

    public function load(): RouteCollection
    {
        $routerConfigs = glob(MODULES_PATH . '*/*/config/routes.php');
        foreach ($routerConfigs as $routerConfig) {
            (require $routerConfig)($this->routeCollection);
        }

        return $this->routeCollection;
    }
}
