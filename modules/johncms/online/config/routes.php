<?php

use Johncms\Online\Controllers\OnlineController;
use Johncms\Users\Middlewares\HasRoleMiddleware;
use League\Route\Router;

return function (Router $router) {
    $router->get('/online/', [OnlineController::class, 'index'])->setName('online.index');
    $router->get('/online/history[/]', [OnlineController::class, 'history'])->setName('online.history');
    $router->get('/online/guests[/]', [OnlineController::class, 'guests'])->middleware(new HasRoleMiddleware('admin'))->setName('online.guests');
    $router->get('/online/ip-activity[/]', [OnlineController::class, 'ipActivity'])->middleware(new HasRoleMiddleware('admin'))->setName('online.ipActivity');
};
