<?php

declare(strict_types=1);

use Johncms\Modules\Downloads\Application\Controllers\NewFilesController;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    $router->get('/downloads/new', NewFilesController::class)->name('downloads.new_files');

    $router->map(['GET', 'POST'], '/downloads', 'modules/downloads/index.php')->name('downloads.index');
};
