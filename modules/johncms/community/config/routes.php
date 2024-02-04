<?php

declare(strict_types=1);

use Johncms\Community\Controllers\CommunityController;
use Johncms\Router\RouteCollection;

return function (RouteCollection $router) {
    $router->group('/community', function (RouteCollection $route) {
        $route->get('/', [CommunityController::class, 'index'])->setName('community.index');
        $route->get('/users/', [CommunityController::class, 'users'])->setName('community.users');
        $route->get('/administration/', [CommunityController::class, 'administration'])->setName('community.administration');
        $route->get('/birthdays/', [CommunityController::class, 'birthdays'])->setName('community.birthdays');
        $route->get('/top/{topType}/', [CommunityController::class, 'top'])
            ->setDefaults(['topType' => 'id'])
            ->setRequirements(['topType' => '[a-z]+'])
            ->setName('community.top');
        $route->get('/search/', [CommunityController::class, 'search'])->setName('community.search');
    });
};
