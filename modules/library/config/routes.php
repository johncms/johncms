<?php

declare(strict_types=1);

use Johncms\Modules\Library\Application\Controllers\TopController;
use Johncms\Modules\Library\Application\Middlewares\LibraryAccessMiddleware;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $router->group('', function (RouteCollection $r): void {
        $r->get('/library/top', TopController::class)->name('library.top');

        $r->map(['GET', 'POST'], '/library', 'modules/library/index.php')->name('library.index');
    })
        ->addMiddleware(LibraryAccessMiddleware::class);
};
