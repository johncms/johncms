<?php

declare(strict_types=1);

use Johncms\Modules\Album\Application\Controllers\AlbumIndexController;
use Johncms\Modules\Album\Application\Controllers\DeleteAlbumController;
use Johncms\Modules\Album\Application\Controllers\DeletePhotoController;
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
use Johncms\Modules\Album\Application\Controllers\VotePhotoController;
use Johncms\Modules\Album\Application\Middlewares\AuthorizedUserMiddleware;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    // The whole module requires an authenticated user. Routes are kept in
    // registration order so the static segments (users, top, user/...) match
    // before the catch-all numeric routes (/album/{al}, /album/photo/{img}).
    $albumGroup = $router->group('', function (RouteCollection $r): void {
        $r->get('/album', AlbumIndexController::class)->name('album.index');

        $r->get('/album/users', UsersListController::class)->name('album.users');
        $r->get('/album/users/{filter}', UsersListController::class)
            ->name('album.users.filter')
            ->requirements(['filter' => 'boys|girls']);

        $r->get('/album/top', TopController::class)->name('album.top');
        $r->get('/album/top/{filter}', TopController::class)
            ->name('album.top.filter')
            ->requirements(['filter' => 'recent-comments|views|downloads|comments|votes|worst|my-comments']);

        // Create a new album for the given user (GET form + POST save).
        $r->get('/album/user/{id:number}/create', [EditAlbumController::class, 'createForm'])->name('album.album.create');
        $r->post('/album/user/{id:number}/create', [EditAlbumController::class, 'createSave'])->name('album.album.create.save');

        $r->get('/album/user/{id:number}', UserAlbumsController::class)->name('album.user');

        // Edit an existing album (GET form + POST save).
        $r->get('/album/{al:number}/edit', [EditAlbumController::class, 'editForm'])->name('album.album.edit');
        $r->post('/album/{al:number}/edit', [EditAlbumController::class, 'editSave'])->name('album.album.edit.save');

        // Delete an album with all its photos (GET confirmation + POST submit).
        $r->get('/album/{al:number}/delete', [DeleteAlbumController::class, 'confirm'])->name('album.album.delete');
        $r->post('/album/{al:number}/delete', [DeleteAlbumController::class, 'delete'])->name('album.album.delete.submit');

        // Reorder an album within the owner's sort order (POST only).
        $r->post('/album/{al:number}/move-up', [SortAlbumController::class, 'moveUp'])->name('album.album.move-up');
        $r->post('/album/{al:number}/move-down', [SortAlbumController::class, 'moveDown'])->name('album.album.move-down');

        // Upload a photo into an album (GET form + POST submit).
        $r->get('/album/{al:number}/upload', [UploadPhotoController::class, 'form'])->name('album.album.upload');
        $r->post('/album/{al:number}/upload', [UploadPhotoController::class, 'upload'])->name('album.album.upload.submit');

        // Edit a photo's description (GET form + POST save).
        $r->get('/album/photo/{img:number}/edit', [EditPhotoController::class, 'form'])->name('album.photo.edit');
        $r->post('/album/photo/{img:number}/edit', [EditPhotoController::class, 'save'])->name('album.photo.edit.save');

        // Move a photo to another album (GET form + POST submit).
        $r->get('/album/photo/{img:number}/move', [MovePhotoController::class, 'form'])->name('album.photo.move');
        $r->post('/album/photo/{img:number}/move', [MovePhotoController::class, 'move'])->name('album.photo.move.submit');

        // Delete a photo with its files, votes and comments (GET confirmation + POST submit).
        $r->get('/album/photo/{img:number}/delete', [DeletePhotoController::class, 'confirm'])->name('album.photo.delete');
        $r->post('/album/photo/{img:number}/delete', [DeletePhotoController::class, 'delete'])->name('album.photo.delete.submit');

        // Vote for a photo (POST only); redirects back to the photo page.
        $r->post('/album/photo/{img:number}/vote/{type}', VotePhotoController::class)
            ->name('album.photo.vote')
            ->requirements(['type' => 'plus|minus']);

        // Photo file download (counts a unique download and redirects to the file).
        $r->get('/album/photo/{img:number}/download', DownloadPhotoController::class)->name('album.photo.download');

        // Photo comments (POST handles adding/replying/deleting comments).
        $r->map(['GET', 'POST'], '/album/photo/{img:number}/comments', PhotoCommentsController::class)->name('album.photo.comments');

        // Single photo viewer (POST handles the password form for protected albums).
        $r->map(['GET', 'POST'], '/album/photo/{img:number}', ShowPhotoController::class)->name('album.photo');

        // Album viewer (POST handles the password form for protected albums).
        $r->map(['GET', 'POST'], '/album/{al:number}', ShowAlbumController::class)->name('album.show');
    });
    $albumGroup->addMiddleware(AuthorizedUserMiddleware::class);
};
