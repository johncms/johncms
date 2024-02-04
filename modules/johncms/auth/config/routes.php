<?php

declare(strict_types=1);

use Johncms\Auth\Controllers\LoginController;
use Johncms\Auth\Controllers\LogoutController;
use Johncms\Auth\Controllers\RegistrationController;
use Johncms\Auth\Middlewares\RegistrationClosedMiddleware;
use Johncms\Router\RouteCollection;
use Johncms\Users\Middlewares\AuthorizedUserMiddleware;
use Johncms\Users\Middlewares\UnauthorizedUserMiddleware;

return function (RouteCollection $router) {
    // Login routes
    $router->get('/login/', [LoginController::class, 'index'])
        ->addMiddleware(UnauthorizedUserMiddleware::class)
        ->setName('login.index');
    $router->post('/login/authorize/', [LoginController::class, 'authorize'])
        ->addMiddleware(UnauthorizedUserMiddleware::class)
        ->setName('login.authorize');

    // Registration routes
    $router->get('/registration/', [RegistrationController::class, 'index'])
        ->addMiddleware(UnauthorizedUserMiddleware::class)
        ->addMiddleware(RegistrationClosedMiddleware::class)
        ->setName('registration.index');

    $router->group('/registration', function (RouteCollection $route) {
        $route->post('/store/', [RegistrationController::class, 'store'])
            ->addMiddleware(RegistrationClosedMiddleware::class)
            ->setName('registration.store');

        $route->get('/confirm-email/', [RegistrationController::class, 'confirmEmail'])
            ->addMiddleware(RegistrationClosedMiddleware::class)
            ->setName('registration.confirmEmail');

        $route->get('/closed/', [RegistrationController::class, 'registrationClosed'])->setName('registration.closed');
    })->addMiddleware(UnauthorizedUserMiddleware::class);

    // Logout routes
    $router->get('/logout/', [LogoutController::class, 'index'])
        ->addMiddleware(AuthorizedUserMiddleware::class)
        ->setName('logout.index');

    $router->post('/logout/confirm/', [LogoutController::class, 'confirm'])
        ->addMiddleware(AuthorizedUserMiddleware::class)
        ->setName('logout.confirm');
};
