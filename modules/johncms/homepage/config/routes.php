<?php

declare(strict_types=1);

use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $router->get('/', \Johncms\Modules\Homepage\Controllers\HomepageController::class)->name('homepage.index');
};
