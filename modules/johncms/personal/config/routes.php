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
        $route->get('/profile[/[{id:number}[/]]]', [ProfileController::class, 'index'])->setName('personal.profile');
        $route->get('/profile/edit/{id:number}[/]', [ProfileController::class, 'edit'])->setName('personal.profile.edit');
        $route->post('/profile/store/{id:number}[/]', [ProfileController::class, 'store'])->setName('personal.profile.store');
    })->lazyMiddleware(AuthorizedUserMiddleware::class);
};
