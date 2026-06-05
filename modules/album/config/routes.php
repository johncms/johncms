<?php

declare(strict_types=1);

use Johncms\Modules\Album\Application\Controllers\AlbumIndexController;
use Johncms\Modules\Album\Application\Middlewares\AuthorizedUserMiddleware;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    // Routes migrated to the new architecture (require an authenticated user).
    // Registered as top-level routes so they take precedence over the legacy
    // catch-all below (the UrlMatcher matches in registration order, and grouped
    // routes are compiled after all top-level routes). Once every action is
    // migrated and the catch-all is removed, these can move into a single
    // auth group (see profile module).
    $router->get('/album', AlbumIndexController::class)
        ->name('album.index')
        ->middleware(AuthorizedUserMiddleware::class);

    // Legacy front controller (migrated action by action).
    $router->map(['GET', 'POST'], '/album/{action}', 'modules/album/index.php')
        ->name('album.legacy')
        ->defaults(['action' => null]);
};
