<?php

declare(strict_types=1);

use Johncms\Modules\Downloads\Application\Controllers\NewFilesController;
use Johncms\Modules\Downloads\Application\Controllers\SearchController;
use Johncms\Modules\Downloads\Application\Controllers\TopFilesController;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    $router->get('/downloads/new', NewFilesController::class)->name('downloads.new_files');
    $router->get('/downloads/top', TopFilesController::class)->name('downloads.top_files');
    $router->get('/downloads/top/{sort}', TopFilesController::class)->name('downloads.top_files_sort');
    $router->get('/downloads/search', SearchController::class)->name('downloads.search');

    $router->map(['GET', 'POST'], '/downloads', 'modules/downloads/index.php')->name('downloads.index');
};
