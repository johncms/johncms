<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Community\Controllers\CommunityController;
use League\Route\RouteGroup;
use League\Route\Router;

/**
 * @psalm-suppress UndefinedInterfaceMethod
 */
return function (Router $router) {
    $router->addPatternMatcher('topType', '[a-z]+');

    $router->group('/community', function (RouteGroup $route) {
        $route->get('/', [CommunityController::class, 'index'])->setName('community.index');
        $route->get('/users', [CommunityController::class, 'users'])->setName('community.users');
        $route->get('/administration', [CommunityController::class, 'administration'])->setName('community.administration');
        $route->get('/birthdays', [CommunityController::class, 'birthdays'])->setName('community.birthdays');
        $route->get('/top[/{type:topType}]', [CommunityController::class, 'top'])->setName('community.top');
        $route->get('/search[/]', [CommunityController::class, 'search'])->setName('community.search');
    });
};
