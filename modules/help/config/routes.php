<?php

declare(strict_types=1);

use Johncms\Modules\Help\Application\Controllers\AdminSmiliesController;
use Johncms\Modules\Help\Application\Controllers\AvatarCatalogController;
use Johncms\Modules\Help\Application\Controllers\AvatarListController;
use Johncms\Modules\Help\Application\Controllers\ForumRulesController;
use Johncms\Modules\Help\Application\Controllers\HelpIndexController;
use Johncms\Modules\Help\Application\Controllers\MySmiliesController;
use Johncms\Modules\Help\Application\Controllers\SetAvatarController;
use Johncms\Modules\Help\Application\Controllers\SetMySmiliesController;
use Johncms\Modules\Help\Application\Controllers\SmiliesCatalogController;
use Johncms\Modules\Help\Application\Controllers\UserSmiliesController;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $router->get('/help', HelpIndexController::class)->name('help.index');

    $router->get('/help/forum', ForumRulesController::class)->name('help.forum');
    $router->get('/help/smilies', SmiliesCatalogController::class)->name('help.smilies');
    $router->get('/help/smilies/my', MySmiliesController::class)->name('help.smilies_my');
    $router->get('/help/smilies/admin', AdminSmiliesController::class)->name('help.smilies_admin');
    $router->post('/help/smilies/set', SetMySmiliesController::class)->name('help.smilies_set');
    $router->get('/help/smilies/{cat}', UserSmiliesController::class)->name('help.smilies_cat');
    $router->get('/help/avatars', AvatarCatalogController::class)->name('help.avatars');
    $router->get('/help/avatars/{id}', AvatarListController::class)->name('help.avatars_list');
    $router->map(['GET', 'POST'], '/help/avatars/{id}/set/{avatar:number}', SetAvatarController::class)->name('help.avatars_set');
};
