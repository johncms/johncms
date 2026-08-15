<?php

declare(strict_types=1);

use Johncms\Modules\Community\Application\Controllers\AdministrationController;
use Johncms\Modules\Community\Application\Controllers\CommunityBirthdaysController;
use Johncms\Modules\Community\Application\Controllers\CommunityIndexController;
use Johncms\Modules\Community\Application\Controllers\CommunitySearchController;
use Johncms\Modules\Community\Application\Controllers\CommunityTopController;
use Johncms\Modules\Community\Application\Controllers\CommunityUsersController;
use Johncms\Modules\Community\Application\Services\CommunityPermissions;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    // Every listing of the community answers the same question, so the gate is on the group
    // rather than repeated at the top of six controllers.
    $community = $router->group('', function (RouteCollection $router): void {
        $router->get('/community', CommunityIndexController::class)->name('community.index');
        $router->get('/community/administration', AdministrationController::class)->name('community.administration');
        $router->get('/community/birthdays', CommunityBirthdaysController::class)->name('community.birthdays');
        $router->get('/community/search', CommunitySearchController::class)->name('community.search');
        $router->get('/community/top/{mod}', CommunityTopController::class)->name('community.top')->defaults(['mod' => null]);
        $router->get('/community/users', CommunityUsersController::class)->name('community.users');
    });
    $community->permission(CommunityPermissions::VIEW);
};
