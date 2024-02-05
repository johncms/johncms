<?php

declare(strict_types=1);

use Johncms\Personal\Controllers\PersonalController;
use Johncms\Personal\Controllers\ProfileController;
use Johncms\Personal\Controllers\SettingsController;
use Johncms\Router\RouteCollection;
use Johncms\Users\Middlewares\AuthorizedUserMiddleware;

return function (RouteCollection $router) {
    $router->get('/personal/', [PersonalController::class, 'index'])->addMiddleware(AuthorizedUserMiddleware::class)->setName('personal.index');
    $router->group('/personal', function (RouteCollection $route) {
        $route->get('/profile/{id:number?}', [ProfileController::class, 'index'])->setName('personal.profile');
        $route->get('/profile/edit/{id:number}/', [ProfileController::class, 'edit'])->setName('personal.profile.edit');
        $route->post('/profile/store/{id:number}/', [ProfileController::class, 'store'])->setName('personal.profile.store');
        $route->post('/profile/avatar/upload/', [ProfileController::class, 'avatarUpload'])->setName('personal.profile.avatarUpload');
        $route->post('/profile/avatar/delete/', [ProfileController::class, 'avatarDelete'])->setName('personal.profile.avatarDelete');

        $route->get('/settings/', [SettingsController::class, 'index'])->setName('personal.settings');
        $route->post('/settings/store/', [SettingsController::class, 'store'])->setName('personal.settings.store');
    })->addMiddleware(AuthorizedUserMiddleware::class);
};
