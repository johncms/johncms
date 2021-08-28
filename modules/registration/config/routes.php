<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use League\Route\RouteGroup;
use League\Route\Router;
use Registration\Controllers\RegistrationController;
use Registration\Middlewares\RegistrationClosedMiddleware;
use Registration\Middlewares\UnauthorizedUserMiddleware;

/**
 * @psalm-suppress UndefinedInterfaceMethod
 */
return function (Router $router) {
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
