<?php

declare(strict_types=1);

use Johncms\Modules\Downloads\Application\Controllers\FavoritesController;
use Johncms\Modules\Downloads\Application\Controllers\NewFilesController;
use Johncms\Modules\Downloads\Application\Controllers\SearchController;
use Johncms\Modules\Downloads\Application\Controllers\TopFilesController;
use Johncms\Modules\Downloads\Application\Controllers\TopUsersController;
use Johncms\Modules\Downloads\Application\Controllers\UserFilesController;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    $router->get('/downloads/favorites', FavoritesController::class)->name('downloads.favorites');
    $router->get('/downloads/new', NewFilesController::class)->name('downloads.new_files');
    $router->get('/downloads/top', TopFilesController::class)->name('downloads.top_files');
    $router->get('/downloads/top/{sort}', TopFilesController::class)->name('downloads.top_files_sort');
    $router->get('/downloads/search', SearchController::class)->name('downloads.search');
    $router->get('/downloads/top-users', TopUsersController::class)->name('downloads.top_users');
    $router->get('/downloads/user-files/{id:number}', UserFilesController::class)->name('downloads.user_files');

    $router->map(['GET', 'POST'], '/downloads', 'modules/downloads/index.php')->name('downloads.index');
};
