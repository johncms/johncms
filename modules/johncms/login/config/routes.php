<?php

declare(strict_types=1);

use Johncms\Modules\Login\Application\Controllers\LoginController;
use Johncms\Modules\Login\Application\Controllers\LogoutController;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $router->map(['GET', 'POST'], '/login', LoginController::class)->name('login.index');
    $router->map(['GET', 'POST'], '/logout', LogoutController::class)->name('login.logout');
};
