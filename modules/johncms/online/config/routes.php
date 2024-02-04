<?php

declare(strict_types=1);

use Johncms\Online\Controllers\OnlineController;
use Johncms\Router\RouteCollection;
use Johncms\Users\Middlewares\HasRoleMiddleware;

return function (RouteCollection $router) {
    $router->get('/online/', [OnlineController::class, 'index'])->setName('online.index');
    $router->get('/online/history/', [OnlineController::class, 'history'])->setName('online.history');
    $router->get('/online/guests/', [OnlineController::class, 'guests'])->addMiddleware(new HasRoleMiddleware('admin'))->setName('online.guests');
    $router->get('/online/ip-activity/', [OnlineController::class, 'ipActivity'])->addMiddleware(new HasRoleMiddleware('admin'))->setName('online.ipActivity');
};
