<?php

declare(strict_types=1);

use Johncms\Modules\Downloads\Application\Controllers\AdditionalFilesController;
use Johncms\Modules\Downloads\Application\Controllers\DownloadPathController;
use Johncms\Modules\Downloads\Application\Controllers\IndexController;
use Johncms\Modules\Downloads\Application\Controllers\CreateCategoryController;
use Johncms\Modules\Downloads\Application\Controllers\DeleteCategoryController;
use Johncms\Modules\Downloads\Application\Controllers\EditCategoryController;
use Johncms\Modules\Downloads\Application\Controllers\RecountController;
use Johncms\Modules\Downloads\Application\Controllers\ScanDirectoryController;
use Johncms\Modules\Downloads\Application\Controllers\FilesModerationController;
use Johncms\Modules\Downloads\Application\Controllers\ImportFileController;
use Johncms\Modules\Downloads\Application\Controllers\CommentsReviewController;
use Johncms\Modules\Downloads\Application\Controllers\DeleteFileController;
use Johncms\Modules\Downloads\Application\Controllers\EditFileController;
use Johncms\Modules\Downloads\Application\Controllers\MoveFileController;
use Johncms\Modules\Downloads\Application\Controllers\EditScreenController;
use Johncms\Modules\Downloads\Application\Controllers\FavoritesController;
use Johncms\Modules\Downloads\Application\Controllers\FileCommentsController;
use Johncms\Modules\Downloads\Application\Controllers\FilePreviewController;
use Johncms\Modules\Downloads\Application\Controllers\FilesUploadController;
use Johncms\Modules\Downloads\Application\Controllers\LoadFileController;
use Johncms\Modules\Downloads\Application\Controllers\NewFilesController;
use Johncms\Modules\Downloads\Application\Controllers\SearchController;
use Johncms\Modules\Downloads\Application\Controllers\TopFilesController;
use Johncms\Modules\Downloads\Application\Controllers\TopUsersController;
use Johncms\Modules\Downloads\Application\Controllers\UserFilesController;
use Johncms\Modules\Downloads\Application\Middlewares\DownloadsAccessMiddleware;
use Johncms\Modules\Downloads\Application\Middlewares\DownloadsAdminMiddleware;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $router->group('', function (RouteCollection $r): void {
        $r->get('/downloads/comments-review', CommentsReviewController::class)->name('downloads.comments_review');
        $r->get('/downloads/favorites', FavoritesController::class)->name('downloads.favorites');
        $r->get('/downloads/new', NewFilesController::class)->name('downloads.new_files');
        $r->get('/downloads/top', TopFilesController::class)->name('downloads.top_files');
        $r->get('/downloads/top/{sort}', TopFilesController::class)->name('downloads.top_files_sort');
        $r->get('/downloads/search', SearchController::class)->name('downloads.search');
        $r->get('/downloads/top-users', TopUsersController::class)->name('downloads.top_users');
        $r->get('/downloads/user-files/{id:number}', UserFilesController::class)->name('downloads.user_files');
        $r->get('/downloads/load/{id:number}', LoadFileController::class)->name('downloads.load_file');
        // Previews are generated once and cached on disk; they are addressed by file id so
        // that no part of a path ever comes from the request.
        $r->get('/downloads/preview/{id:number}', [FilePreviewController::class, 'file'])->name('downloads.file_preview');
        $r->get('/downloads/preview/{id:number}/{name}', [FilePreviewController::class, 'screen'])
            ->name('downloads.screen_preview')
            ->requirements(['name' => '[A-Za-z0-9_.\-]+']);
        $r->map(['GET', 'POST'], '/downloads/comments/{id:number}', FileCommentsController::class)->name('downloads.file_comments');
        $r->map(['GET', 'POST'], '/downloads/upload/{id:number}', FilesUploadController::class)->name('downloads.upload');

        $r->map(['GET', 'POST'], '/downloads', IndexController::class)->name('downloads.index');
    })
        ->addMiddleware(DownloadsAccessMiddleware::class);

    $router->group('', function (RouteCollection $r): void {
        $r->map(['GET', 'POST'], '/downloads/delete-file/{id:number}', DeleteFileController::class)->name('downloads.delete_file');
        $r->map(['GET', 'POST'], '/downloads/edit-file/{id:number}', EditFileController::class)->name('downloads.edit_file');
        $r->map(['GET', 'POST'], '/downloads/edit-screen/{id:number}', EditScreenController::class)->name('downloads.edit_screen');
        $r->map(['GET', 'POST'], '/downloads/additional-files/{id:number}', AdditionalFilesController::class)->name('downloads.additional_files');
        $r->get('/downloads/move-file/{id:number}', MoveFileController::class)->name('downloads.move_file');
        $r->map(['GET', 'POST'], '/downloads/import/{id:number}', ImportFileController::class)->name('downloads.import');
        $r->map(['GET', 'POST'], '/downloads/moderation', FilesModerationController::class)->name('downloads.moderation');
        $r->map(['GET', 'POST'], '/downloads/categories/create', CreateCategoryController::class)->name('downloads.create_category');
        $r->map(['GET', 'POST'], '/downloads/categories/{id:number}/edit', EditCategoryController::class)->name('downloads.edit_category');
        $r->map(['GET', 'POST'], '/downloads/categories/{id:number}/delete', DeleteCategoryController::class)->name('downloads.delete_category');
        $r->get('/downloads/recount', RecountController::class)->name('downloads.recount');
        $r->get('/downloads/scan-dir', ScanDirectoryController::class)->name('downloads.scan_dir');
    })
        ->addMiddleware(DownloadsAccessMiddleware::class)
        ->addMiddleware(DownloadsAdminMiddleware::class);

    // Slug-based catch-all — must be registered last so specific routes take priority
    $router->group('', function (RouteCollection $r): void {
        $r->map(['GET', 'POST'], '/downloads/{categoryPath}', DownloadPathController::class)->name('downloads.path')->requirements(['categoryPath' => '[a-z0-9\\-/]+']);
    })
        ->addMiddleware(DownloadsAccessMiddleware::class);
};
