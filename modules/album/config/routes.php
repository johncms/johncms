<?php

declare(strict_types=1);

use Johncms\Modules\Album\Application\Controllers\AlbumIndexController;
use Johncms\Modules\Album\Application\Controllers\DownloadPhotoController;
use Johncms\Modules\Album\Application\Controllers\ShowAlbumController;
use Johncms\Modules\Album\Application\Controllers\ShowPhotoController;
use Johncms\Modules\Album\Application\Controllers\TopController;
use Johncms\Modules\Album\Application\Controllers\UserAlbumsController;
use Johncms\Modules\Album\Application\Controllers\UsersListController;
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

    $router->get('/album/users', UsersListController::class)
        ->name('album.users')
        ->middleware(AuthorizedUserMiddleware::class);
    $router->get('/album/users/{filter}', UsersListController::class)
        ->name('album.users.filter')
        ->requirements(['filter' => 'boys|girls'])
        ->middleware(AuthorizedUserMiddleware::class);

    $router->get('/album/top', TopController::class)
        ->name('album.top')
        ->middleware(AuthorizedUserMiddleware::class);
    $router->get('/album/top/{filter}', TopController::class)
        ->name('album.top.filter')
        ->requirements(['filter' => 'recent-comments|views|downloads|comments|votes|worst|my-comments'])
        ->middleware(AuthorizedUserMiddleware::class);

    $router->get('/album/user/{id}', UserAlbumsController::class)
        ->name('album.user')
        ->requirements(['id' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);

    // Photo file download (counts a unique download and redirects to the file).
    $router->get('/album/photo/{img}/download', DownloadPhotoController::class)
        ->name('album.photo.download')
        ->requirements(['img' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);

    // Single photo viewer (POST handles the password form for protected albums).
    $router->map(['GET', 'POST'], '/album/photo/{img}', ShowPhotoController::class)
        ->name('album.photo')
        ->requirements(['img' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);

    // Album viewer (POST handles the password form for protected albums).
    $router->map(['GET', 'POST'], '/album/{al}', ShowAlbumController::class)
        ->name('album.show')
        ->requirements(['al' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);

    // Legacy front controller (migrated action by action).
    $router->map(['GET', 'POST'], '/album/{action}', 'modules/album/index.php')
        ->name('album.legacy')
        ->defaults(['action' => null]);
};
