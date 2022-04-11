<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Personal\Controllers\PersonalController;
use Johncms\Personal\Controllers\ProfileController;
use Johncms\Personal\Controllers\SettingsController;
use Johncms\Users\Middlewares\AuthorizedUserMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

return function (Router $router) {
    $router->get('/personal[/]', [PersonalController::class, 'index'])->lazyMiddlewares([AuthorizedUserMiddleware::class])->setName('personal.index');
    $router->group('/personal', function (RouteGroup $route) {
        $route->get('/profile[/[{id:number}[/]]]', [ProfileController::class, 'index'])->setName('personal.profile');
        $route->get('/profile/edit/{id:number}[/]', [ProfileController::class, 'edit'])->setName('personal.profile.edit');
        $route->post('/profile/store/{id:number}[/]', [ProfileController::class, 'store'])->setName('personal.profile.store');
        $route->post('/profile/avatar/upload[/]', [ProfileController::class, 'avatarUpload'])->setName('personal.profile.avatarUpload');
        $route->post('/profile/avatar/delete[/]', [ProfileController::class, 'avatarDelete'])->setName('personal.profile.avatarDelete');

        $route->get('/settings[/]', [SettingsController::class, 'index'])->setName('personal.settings');
        $route->post('/settings/store[/]', [SettingsController::class, 'store'])->setName('personal.settings.store');
    })->lazyMiddleware(AuthorizedUserMiddleware::class);

    $router->get('/personal/{id:number}[/]', [PersonalController::class, 'index'])->setName('personal.index');


    // Профиль пользователя

};
