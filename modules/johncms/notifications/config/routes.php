<?php

declare(strict_types=1);

use Johncms\Modules\Notifications\Application\Controllers\ClearController;
use Johncms\Modules\Notifications\Application\Controllers\IndexController;
use Johncms\Modules\Notifications\Application\Controllers\SettingsController;
use Johncms\Modules\Notifications\Application\Middlewares\AuthorizedUserMiddleware;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $notificationsGroup = $router->group('', function (RouteCollection $r): void {
        $r->get('/notifications', IndexController::class)->name('notifications.index');
        $r->map(['GET', 'POST'], '/notifications/settings', SettingsController::class)->name('notifications.settings');
        $r->post('/notifications/clear', ClearController::class)->name('notifications.clear');
    });
    $notificationsGroup->addMiddleware(AuthorizedUserMiddleware::class);
};
