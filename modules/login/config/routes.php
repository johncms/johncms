<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use League\Route\Router;
use Login\Controllers\LoginController;
use Registration\Middlewares\UnauthorizedUserMiddleware;

/**
 * @psalm-suppress UndefinedInterfaceMethod
 */
return function (Router $router) {
    $router->get('/login[/]', [LoginController::class, 'index'])
        ->lazyMiddlewares([UnauthorizedUserMiddleware::class])
        ->setName('login.index');
    $router->post('/login/authorize[/]', [LoginController::class, 'authorize'])
        ->lazyMiddlewares([UnauthorizedUserMiddleware::class])
        ->setName('login.authorize');
};
