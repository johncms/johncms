<?php

declare(strict_types=1);

use Johncms\Modules\Redirect\Application\Controllers\RedirectController;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $router->map(['GET', 'POST'], '/redirect', RedirectController::class)->name('redirect.index');
};
