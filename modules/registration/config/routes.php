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

return function (Router $router) {
    $router->get('/registration[/]', [RegistrationController::class, 'index'])->setName('registration.index');
    $router->group('/registration', function (RouteGroup $route) {
        $route->post('/store[/]', [RegistrationController::class, 'store'])->setName('registration.store');
        $route->get('/confirm-email[/]', [RegistrationController::class, 'confirmEmail'])->setName('registration.confirmEmail');
    });
};
