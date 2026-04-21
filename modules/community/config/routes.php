<?php

declare(strict_types=1);

use Johncms\Modules\Community\Application\Controllers\AdministrationController;
use Johncms\Modules\Community\Application\Controllers\CommunityBirthdaysController;
use Johncms\Modules\Community\Application\Controllers\CommunityIndexController;
use Johncms\Modules\Community\Application\Controllers\CommunitySearchController;
use Johncms\Modules\Community\Application\Controllers\CommunityTopController;
use Johncms\Modules\Community\Application\Controllers\CommunityUsersController;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    $router->get('/community', CommunityIndexController::class);
    $router->get('/community/administration', AdministrationController::class);
    $router->get('/community/birthdays', CommunityBirthdaysController::class);
    $router->get('/community/search', CommunitySearchController::class);
    $router->get('/community/top/{mod}', CommunityTopController::class)->defaults(['mod' => null]);
    $router->get('/community/users', CommunityUsersController::class);
};
