<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Auth\Middlewares\AuthorizedUserMiddleware;
use Johncms\Personal\Controllers\PersonalController;
use Johncms\Personal\Controllers\ProfileController;
use League\Route\RouteGroup;
use League\Route\Router;

return function (Router $router) {
    $router->get('/personal[/]', [PersonalController::class, 'index'])->lazyMiddlewares([AuthorizedUserMiddleware::class])->setName('personal.index');
    $router->group('/personal', function (RouteGroup $route) {
        $route->get('/profile[/]', [ProfileController::class, 'index'])->setName('personal.profile');
    })->lazyMiddleware(AuthorizedUserMiddleware::class);
};
