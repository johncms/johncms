<?php

declare(strict_types=1);

use Johncms\Community\Controllers\CommunityController;
use Johncms\Router\RouteCollection;

return function (RouteCollection $router) {
    // TODO: Add patterns
    // $router->addPatternMatcher('topType', '[a-z]+');

    $router->group('/community', function (RouteCollection $route) {
        $route->get('/', [CommunityController::class, 'index'])->setName('community.index');
        $route->get('/users', [CommunityController::class, 'users'])->setName('community.users');
        $route->get('/administration', [CommunityController::class, 'administration'])->setName('community.administration');
        $route->get('/birthdays', [CommunityController::class, 'birthdays'])->setName('community.birthdays');
        $route->get('/top[/{type:topType}]', [CommunityController::class, 'top'])->setName('community.top');
        $route->get('/search[/]', [CommunityController::class, 'search'])->setName('community.search');
    });
};
