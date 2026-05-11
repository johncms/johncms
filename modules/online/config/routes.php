<?php

declare(strict_types=1);

use Johncms\Modules\Online\Application\Controllers\GuestController;
use Johncms\Modules\Online\Application\Controllers\HistoryController;
use Johncms\Modules\Online\Application\Controllers\IndexController;
use Johncms\Modules\Online\Application\Controllers\IpController;
use Johncms\Modules\Online\Application\Middlewares\OnlineAdminMiddleware;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $router->get('/online/history', HistoryController::class)->name('online.history');
    $router->get('/online', IndexController::class)->name('online.index');

    $router->group('', function (RouteCollection $r): void {
        $r->get('/online/guest', GuestController::class)->name('online.guest');
        $r->get('/online/ip', IpController::class)->name('online.ip');
    })
        ->addMiddleware(OnlineAdminMiddleware::class);
};
