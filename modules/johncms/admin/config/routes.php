<?php

use Johncms\Admin\Controllers\DashboardController;
use Johncms\Admin\Controllers\System\DebugBarController;
use League\Route\Router;

return function (Router $router) {
    $router->get('/_debugbar/open/', [DebugBarController::class, 'index'])->setName('debugBar.open');

    // Dashboard
    // TODO: Add middleware to check rights
    $router->get('/admin[/]', [DashboardController::class, 'index'])->setName('admin.dashboard');
};
