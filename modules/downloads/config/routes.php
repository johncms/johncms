<?php

declare(strict_types=1);

use Johncms\Modules\Downloads\Application\Controllers\CommentsReviewController;
use Johncms\Modules\Downloads\Application\Controllers\DeleteFileController;
use Johncms\Modules\Downloads\Application\Controllers\EditFileController;
use Johncms\Modules\Downloads\Application\Controllers\FavoritesController;
use Johncms\Modules\Downloads\Application\Controllers\FileCommentsController;
use Johncms\Modules\Downloads\Application\Controllers\FilesUploadController;
use Johncms\Modules\Downloads\Application\Controllers\LoadFileController;
use Johncms\Modules\Downloads\Application\Controllers\NewFilesController;
use Johncms\Modules\Downloads\Application\Controllers\SearchController;
use Johncms\Modules\Downloads\Application\Controllers\TopFilesController;
use Johncms\Modules\Downloads\Application\Controllers\TopUsersController;
use Johncms\Modules\Downloads\Application\Controllers\UserFilesController;
use Johncms\Modules\Downloads\Application\Controllers\ViewFileController;
use Johncms\Modules\Downloads\Application\Middlewares\DownloadsAccessMiddleware;
use Johncms\Modules\Downloads\Application\Middlewares\DownloadsAdminMiddleware;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    $group = $router->group('', function (RouteCollection $r): void {
        $r->get('/downloads/comments-review', CommentsReviewController::class)->name('downloads.comments_review');
        $r->get('/downloads/favorites', FavoritesController::class)->name('downloads.favorites');
        $r->get('/downloads/new', NewFilesController::class)->name('downloads.new_files');
        $r->get('/downloads/top', TopFilesController::class)->name('downloads.top_files');
        $r->get('/downloads/top/{sort}', TopFilesController::class)->name('downloads.top_files_sort');
        $r->get('/downloads/search', SearchController::class)->name('downloads.search');
        $r->get('/downloads/top-users', TopUsersController::class)->name('downloads.top_users');
        $r->get('/downloads/user-files/{id:number}', UserFilesController::class)->name('downloads.user_files');
        $r->get('/downloads/files/{id:number}', ViewFileController::class)->name('downloads.view_file');
        $r->get('/downloads/load/{id:number}', LoadFileController::class)->name('downloads.load_file');
        $r->map(['GET', 'POST'], '/downloads/comments/{id:number}', FileCommentsController::class)->name('downloads.file_comments');
        $r->map(['GET', 'POST'], '/downloads/upload/{id:number}', FilesUploadController::class)->name('downloads.upload');

        $r->map(['GET', 'POST'], '/downloads', 'modules/downloads/index.php')->name('downloads.index');
    });
    $group->addMiddleware(DownloadsAccessMiddleware::class);

    $adminGroup = $router->group('', function (RouteCollection $r): void {
        $r->map(['GET', 'POST'], '/downloads/delete-file/{id:number}', DeleteFileController::class)->name('downloads.delete_file');
        $r->map(['GET', 'POST'], '/downloads/edit-file/{id:number}', EditFileController::class)->name('downloads.edit_file');
    });
    $adminGroup->addMiddleware(DownloadsAccessMiddleware::class);
    $adminGroup->addMiddleware(DownloadsAdminMiddleware::class);
};
