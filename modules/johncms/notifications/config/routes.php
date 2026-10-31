<?php

declare(strict_types=1);

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Notifications\Application\Controllers\ClearController;
use Johncms\Modules\Notifications\Application\Controllers\IndexController;
use Johncms\Modules\Notifications\Application\Controllers\SettingsController;
use Johncms\Modules\Notifications\Application\Middlewares\AuthorizedUserMiddleware;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $admin = $router->group('', function (RouteCollection $r): void {
        $r->get('/admin/notifications', [IndexController::class, 'admin'])->name('notifications.admin.index');
        $r->post('/admin/notifications/clear', [ClearController::class, 'admin'])->name('notifications.admin.clear');
        $r->map(['GET', 'POST'], '/admin/notifications/settings', [SettingsController::class, 'admin'])->name('notifications.admin.settings');
    });
    $admin->permission(CorePermissions::ADMIN_ACCESS)->adminArea();

    $notificationsGroup = $router->group('', function (RouteCollection $r): void {
        $r->get('/notifications', IndexController::class)->name('notifications.index');
        $r->map(['GET', 'POST'], '/notifications/settings', SettingsController::class)->name('notifications.settings');
        $r->post('/notifications/clear', ClearController::class)->name('notifications.clear');
    });
    $notificationsGroup->addMiddleware(AuthorizedUserMiddleware::class);
};
