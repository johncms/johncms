<?php

declare(strict_types=1);

use Johncms\Modules\Admin\Application\Controllers\DashboardController;
use Johncms\Modules\Admin\Application\Controllers\System\SystemCheckController;
use Johncms\Modules\Admin\Application\Controllers\Users\StaffListController;
use Johncms\Modules\Admin\Application\Controllers\Users\UsersController;
use Johncms\Modules\Admin\Application\Middlewares\AdminAccessMiddleware;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    // Public route: the admin login screen (no access guard).
    $router->map(['GET', 'POST'], '/admin/login', [UsersController::class, 'login'])->name('admin.login');

    if ($user->rights >= 6 && $user->isValid()) {
        $router->map(['GET', 'POST'], '/admin/system_check', [SystemCheckController::class, 'index'])->name('admin.system_check');
    }

    // Routes migrated to the new architecture. The whole group requires an
    // administrator (rights >= 7); higher-privilege actions add their own guards.
    // Static segments are registered before the legacy catch-all below so they
    // win on first match (plain UrlMatcher, registration order).
    $adminGroup = $router->group('', function (RouteCollection $r): void {
        $r->get('/admin', DashboardController::class)->name('admin.index');
        $r->get('/admin/staff', StaffListController::class)->name('admin.staff');
    });
    $adminGroup->addMiddleware(AdminAccessMiddleware::class);

    // Legacy query-param dispatcher for actions not yet migrated. Negative priority
    // keeps this catch-all last in the compiled collection, so every migrated route
    // (now and in the future) is matched before it — group routes are compiled after
    // top-level routes, so registration order alone is not enough.
    $router->map(['GET', 'POST'], '/admin/{action}', 'modules/admin/index.php')
        ->name('admin.legacy')
        ->priority(-100)
        ->defaults(['action' => null]);
};
