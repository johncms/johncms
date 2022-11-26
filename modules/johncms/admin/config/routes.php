<?php

use Johncms\Admin\AdminPermissions;
use Johncms\Admin\Controllers\Users\AuthController;
use Johncms\Admin\Controllers\DashboardController;
use Johncms\Admin\Controllers\System\DebugBarController;
use Johncms\Admin\Controllers\Modules\ModulesController;
use Johncms\Admin\Controllers\Users\UsersController;
use Johncms\Admin\Middlewares\AdminAuthorizedUserMiddleware;
use Johncms\Admin\Middlewares\AdminUnauthorizedUserMiddleware;
use Johncms\Users\Middlewares\HasAnyRoleMiddleware;
use Johncms\Users\Middlewares\HasPermissionMiddleware;
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
        $routeGroup->get('/login[/]', [AuthController::class, 'index'])->setName('admin.login');
        $routeGroup->post('/login/authorize[/]', [AuthController::class, 'authorize'])->setName('admin.authorize');
    })->lazyMiddleware(AdminUnauthorizedUserMiddleware::class);

    $router->group('/admin/users', function (RouteGroup $routeGroup) {
        $routeGroup->get('[/]', [UsersController::class, 'index'])->setName('admin.users');
        $routeGroup->get('/list[/]', [UsersController::class, 'userList'])->setName('admin.userList');
        $routeGroup->get('/create[/]', [UsersController::class, 'create'])->setName('admin.createUser');
        $routeGroup->get('/edit/{id:number}[/]', [UsersController::class, 'edit'])->setName('admin.editUser');
        $routeGroup->post('/store[/]', [UsersController::class, 'store'])->setName('admin.storeUser');
        $routeGroup->post('/delete[/]', [UsersController::class, 'delete'])->setName('admin.deleteUser');
    })->middleware(new HasPermissionMiddleware(AdminPermissions::USER_MANAGEMENT));


    $router->group('/admin/modules', function (RouteGroup $routeGroup) {
        $routeGroup->get('[/]', [ModulesController::class, 'index'])->setName('admin.modules');
        $routeGroup->get('/add[/]', [ModulesController::class, 'add'])->setName('admin.modules.add');
        $routeGroup->post('/add[/]', [ModulesController::class, 'add']);
    })->middleware(new HasPermissionMiddleware(AdminPermissions::USER_MANAGEMENT));
};
