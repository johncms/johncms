<?php

declare(strict_types=1);

use Johncms\Homepage\Controllers\HomepageController;
use Johncms\Router\RouteCollection;

return function (RouteCollection $router) {
    $router->get('/', [HomepageController::class, 'index'])->setName('homepage.index');
};
