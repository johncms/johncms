<?php

declare(strict_types=1);

use Johncms\Modules\Album\Application\Controllers\AlbumIndexController;
use Johncms\Modules\Album\Application\Controllers\DeleteAlbumController;
use Johncms\Modules\Album\Application\Controllers\DownloadPhotoController;
use Johncms\Modules\Album\Application\Controllers\EditAlbumController;
use Johncms\Modules\Album\Application\Controllers\EditPhotoController;
use Johncms\Modules\Album\Application\Controllers\MovePhotoController;
use Johncms\Modules\Album\Application\Controllers\PhotoCommentsController;
use Johncms\Modules\Album\Application\Controllers\ShowAlbumController;
use Johncms\Modules\Album\Application\Controllers\ShowPhotoController;
use Johncms\Modules\Album\Application\Controllers\SortAlbumController;
use Johncms\Modules\Album\Application\Controllers\UploadPhotoController;
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

    // Create a new album for the given user (GET form + POST save).
    $router->get('/album/user/{id}/create', [EditAlbumController::class, 'createForm'])
        ->name('album.album.create')
        ->requirements(['id' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);
    $router->post('/album/user/{id}/create', [EditAlbumController::class, 'createSave'])
        ->name('album.album.create.save')
        ->requirements(['id' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);

    $router->get('/album/user/{id}', UserAlbumsController::class)
        ->name('album.user')
        ->requirements(['id' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);

    // Edit an existing album (GET form + POST save).
    $router->get('/album/{al}/edit', [EditAlbumController::class, 'editForm'])
        ->name('album.album.edit')
        ->requirements(['al' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);
    $router->post('/album/{al}/edit', [EditAlbumController::class, 'editSave'])
        ->name('album.album.edit.save')
        ->requirements(['al' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);

    // Delete an album with all its photos (GET confirmation + POST submit).
    $router->get('/album/{al}/delete', [DeleteAlbumController::class, 'confirm'])
        ->name('album.album.delete')
        ->requirements(['al' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);
    $router->post('/album/{al}/delete', [DeleteAlbumController::class, 'delete'])
        ->name('album.album.delete.submit')
        ->requirements(['al' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);

    // Reorder an album within the owner's sort order (POST only).
    $router->post('/album/{al}/move-up', [SortAlbumController::class, 'moveUp'])
        ->name('album.album.move-up')
        ->requirements(['al' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);
    $router->post('/album/{al}/move-down', [SortAlbumController::class, 'moveDown'])
        ->name('album.album.move-down')
        ->requirements(['al' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);

    // Upload a photo into an album (GET form + POST submit).
    $router->get('/album/{al}/upload', [UploadPhotoController::class, 'form'])
        ->name('album.album.upload')
        ->requirements(['al' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);
    $router->post('/album/{al}/upload', [UploadPhotoController::class, 'upload'])
        ->name('album.album.upload.submit')
        ->requirements(['al' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);

    // Edit a photo's description (GET form + POST save).
    $router->get('/album/photo/{img}/edit', [EditPhotoController::class, 'form'])
        ->name('album.photo.edit')
        ->requirements(['img' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);
    $router->post('/album/photo/{img}/edit', [EditPhotoController::class, 'save'])
        ->name('album.photo.edit.save')
        ->requirements(['img' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);

    // Move a photo to another album (GET form + POST submit).
    $router->get('/album/photo/{img}/move', [MovePhotoController::class, 'form'])
        ->name('album.photo.move')
        ->requirements(['img' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);
    $router->post('/album/photo/{img}/move', [MovePhotoController::class, 'move'])
        ->name('album.photo.move.submit')
        ->requirements(['img' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);

    // Photo file download (counts a unique download and redirects to the file).
    $router->get('/album/photo/{img}/download', DownloadPhotoController::class)
        ->name('album.photo.download')
        ->requirements(['img' => '\d+'])
        ->middleware(AuthorizedUserMiddleware::class);

    // Photo comments (POST handles adding/replying/deleting comments).
    $router->map(['GET', 'POST'], '/album/photo/{img}/comments', PhotoCommentsController::class)
        ->name('album.photo.comments')
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
