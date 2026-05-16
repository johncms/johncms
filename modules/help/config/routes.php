<?php

declare(strict_types=1);

use Johncms\Modules\Help\Application\Controllers\AvatarCatalogController;
use Johncms\Modules\Help\Application\Controllers\ForumRulesController;
use Johncms\Modules\Help\Application\Controllers\AvatarListController;
use Johncms\Modules\Help\Application\Controllers\SetAvatarController;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    $router->map(['GET', 'POST'], '/help', 'modules/help/index.php')->name('help.index');

    $router->get('/help/forum', ForumRulesController::class)->name('help.forum');
    $router->get('/help/avatars', AvatarCatalogController::class)->name('help.avatars');
    $router->get('/help/avatars/{id}', AvatarListController::class)->name('help.avatars_list');
    $router->map(['GET', 'POST'], '/help/avatars/{id}/set/{avatar:number}', SetAvatarController::class)->name('help.avatars_set');
};
