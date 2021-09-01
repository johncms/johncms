<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Auth\Controllers\LoginController;
use Auth\Controllers\RegistrationController;
use Auth\Middlewares\RegistrationClosedMiddleware;
use Auth\Middlewares\UnauthorizedUserMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

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

    $router->get('/registration[/]', [RegistrationController::class, 'index'])
        ->lazyMiddlewares([UnauthorizedUserMiddleware::class, RegistrationClosedMiddleware::class])
        ->setName('registration.index');

    $router->group('/registration', function (RouteGroup $route) {
        $route->post('/store[/]', [RegistrationController::class, 'store'])
            ->lazyMiddleware(RegistrationClosedMiddleware::class)
            ->setName('registration.store');

        $route->get('/confirm-email[/]', [RegistrationController::class, 'confirmEmail'])
            ->lazyMiddleware(RegistrationClosedMiddleware::class)
            ->setName('registration.confirmEmail');

        $route->get('/closed[/]', [RegistrationController::class, 'registrationClosed'])->setName('registration.closed');
    })->lazyMiddleware(UnauthorizedUserMiddleware::class);
};
