<?php

declare(strict_types=1);

use Johncms\Modules\Auth\Application\Controllers\ConfirmEmailChangeController;
use Johncms\Modules\Auth\Application\Controllers\ConfirmRegistrationEmailController;
use Johncms\Modules\Auth\Application\Controllers\LoginController;
use Johncms\Modules\Auth\Application\Controllers\LogoutController;
use Johncms\Modules\Auth\Application\Controllers\MovedProfileUrlsController;
use Johncms\Modules\Auth\Application\Controllers\RegistrationController;
use Johncms\Modules\Auth\Application\Controllers\RestorePasswordController;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $router->map(['GET', 'POST'], '/login', LoginController::class)->name('login.index');
    $router->map(['GET', 'POST'], '/logout', LogoutController::class)->name('login.logout');

    $router->map(['GET', 'POST'], '/registration', RegistrationController::class)->name('registration.index');
    $router->get('/registration/confirm-email', ConfirmRegistrationEmailController::class)->name('registration.confirm_email');

    // Screens a visitor without a session needs: the code from the e-mail is what grants access.
    $router->get('/password-recovery', [RestorePasswordController::class, 'form'])->name('password-recovery.index');
    $router->post('/password-recovery', [RestorePasswordController::class, 'send'])->name('password-recovery.send');
    $router->get('/password-recovery/{id:number}/{code}', [RestorePasswordController::class, 'setForm'])
        ->name('password-recovery.set');
    $router->post('/password-recovery/{id:number}/{code}', [RestorePasswordController::class, 'set'])
        ->name('password-recovery.set.submit');
    $router->get('/confirm-email/{id:number}/{code}', ConfirmEmailChangeController::class)->name('email-change.confirm');

    // Where those three screens used to live. Links already sent by e-mail point here.
    $router->get('/profile/password-recovery', [MovedProfileUrlsController::class, 'passwordRecovery'])
        ->name('profile.password-recovery');
    $router->get('/profile/password-recovery/set/{id:number}/{code}', [MovedProfileUrlsController::class, 'passwordRecoverySet'])
        ->name('profile.password-recovery.set');
    $router->get('/profile/confirm-email/{id:number}/{code}', [MovedProfileUrlsController::class, 'confirmEmailChange'])
        ->name('profile.confirm-email');
};
