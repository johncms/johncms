<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Auth\Controllers\LoginController;
use Johncms\Auth\Controllers\LogoutController;
use Johncms\Auth\Controllers\RegistrationController;
use Johncms\Auth\Middlewares\AuthorizedUserMiddleware;
use Johncms\Auth\Middlewares\RegistrationClosedMiddleware;
use Johncms\Auth\Middlewares\UnauthorizedUserMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

/**
 * @psalm-suppress UndefinedInterfaceMethod
 */
return function (Router $router) {
    // Login routes
    $router->get('/login[/]', [LoginController::class, 'index'])
        ->lazyMiddlewares([UnauthorizedUserMiddleware::class])
        ->setName('login.index');
    $router->post('/login/authorize[/]', [LoginController::class, 'authorize'])
        ->lazyMiddlewares([UnauthorizedUserMiddleware::class])
        ->setName('login.authorize');

    // Registration routes
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

    // Logout routes
    $router->get('/logout[/]', [LogoutController::class, 'index'])
        ->lazyMiddlewares([AuthorizedUserMiddleware::class])
        ->setName('logout.index');

    $router->post('/logout/confirm[/]', [LogoutController::class, 'confirm'])
        ->lazyMiddleware(AuthorizedUserMiddleware::class)
        ->setName('logout.confirm');
};
