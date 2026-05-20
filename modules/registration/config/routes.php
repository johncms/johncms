<?php

declare(strict_types=1);

use Johncms\Modules\Registration\Application\Controllers\ConfirmEmailController;
use Johncms\Modules\Registration\Application\Controllers\RegistrationController;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $router->map(['GET', 'POST'], '/registration', RegistrationController::class)->name('registration.index');
    $router->get('/registration/confirm-email', ConfirmEmailController::class)->name('registration.confirm_email');
};
