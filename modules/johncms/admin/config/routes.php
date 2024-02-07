<?php

use Johncms\Admin\AdminPermissions;
use Johncms\Admin\Controllers\System\SystemCheckController;
use Johncms\Admin\Controllers\Users\AuthController;
use Johncms\Admin\Controllers\DashboardController;
use Johncms\Admin\Controllers\System\DebugBarController;
use Johncms\Admin\Controllers\Modules\ModulesController;
use Johncms\Admin\Controllers\Users\UsersController;
use Johncms\Admin\Middlewares\AdminAuthorizedUserMiddleware;
use Johncms\Admin\Middlewares\AdminUnauthorizedUserMiddleware;
use Johncms\Router\RouteCollection;
use Johncms\Users\Middlewares\HasAnyRoleMiddleware;
use Johncms\Users\Middlewares\HasPermissionMiddleware;

return function (RouteCollection $router) {
    $router->get('/_debugbar/open/', [DebugBarController::class, 'index'])->setName('debugBar.open');

    // Dashboard
    // TODO: Add middleware to check rights
    $router->get('/admin/', [DashboardController::class, 'index'])
        ->setName('admin.dashboard')
        ->addMiddleware(AdminAuthorizedUserMiddleware::class)
        ->addMiddleware(HasAnyRoleMiddleware::class);

    $router->group('/admin/', function (RouteCollection $routeGroup) {
        // Login routes
        $routeGroup->get('/login/', [AuthController::class, 'index'])->setName('admin.login');
        $routeGroup->post('/login/authorize/', [AuthController::class, 'authorize'])->setName('admin.authorize');
    })->addMiddleware(AdminUnauthorizedUserMiddleware::class);

    $router->group('/admin/users', function (RouteCollection $routeGroup) {
        $routeGroup->get('/', [UsersController::class, 'index'])->setName('admin.users');
        $routeGroup->get('/list/', [UsersController::class, 'userList'])->setName('admin.userList');
        $routeGroup->get('/create/', [UsersController::class, 'create'])->setName('admin.createUser');
        $routeGroup->get('/edit/{id:number}/', [UsersController::class, 'edit'])->setName('admin.editUser');
        $routeGroup->post('/store/', [UsersController::class, 'store'])->setName('admin.storeUser');
        $routeGroup->post('/delete/', [UsersController::class, 'delete'])->setName('admin.deleteUser');
    })->addMiddleware(new HasPermissionMiddleware(AdminPermissions::USER_MANAGEMENT));


    $router->group('/admin/system/modules', function (RouteCollection $routeGroup) {
        $routeGroup->get('/', [ModulesController::class, 'index'])->setName('admin.modules');
        $routeGroup->map(['GET', 'POST'], '/add/', [ModulesController::class, 'add'])->setName('admin.modules.add');
        $routeGroup->map(['GET', 'POST'], '/delete/', [ModulesController::class, 'delete'])->setName('admin.modules.delete');
        $routeGroup->map(['GET', 'POST'], '/update/', [ModulesController::class, 'update'])->setName('admin.modules.update');
    })->addMiddleware(new HasPermissionMiddleware(AdminPermissions::USER_MANAGEMENT));

    $router->get('/admin/system/check/', [SystemCheckController::class, 'index'])->setName('admin.system.check');
};
