<?php

use Johncms\Admin\Controllers\Users\UsersController;
use Johncms\Admin\Controllers\DashboardController;
use Johncms\Admin\Controllers\System\DebugBarController;
use Johncms\Admin\Middlewares\AdminAuthorizedUserMiddleware;
use Johncms\Admin\Middlewares\AdminUnauthorizedUserMiddleware;
use Johncms\Users\Middlewares\HasAnyRoleMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

return function (Router $router) {
    $router->get('/_debugbar/open/', [DebugBarController::class, 'index'])->setName('debugBar.open');

    // Dashboard
    // TODO: Add middleware to check rights
    $router->get('/admin[/]', [DashboardController::class, 'index'])
        ->setName('admin.dashboard')
        ->lazyMiddleware(AdminAuthorizedUserMiddleware::class)
        ->lazyMiddleware(HasAnyRoleMiddleware::class);

    $router->group('/admin', function (RouteGroup $routeGroup) {
        // Login routes
        $routeGroup->get('/login[/]', [UsersController::class, 'index'])
            ->setName('admin.login');
        $routeGroup->post('/login/authorize[/]', [UsersController::class, 'authorize'])
            ->setName('admin.authorize');
    })->lazyMiddleware(AdminUnauthorizedUserMiddleware::class);
};
