<?php

declare(strict_types=1);

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Http\Controller\ExternalAuthController;
use Johncms\Http\Controller\ImpersonationController;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    // Browsing as another user. Core routes rather than routes of the admin module: leaving has to
    // work from every page of the site, and the banner offering it is drawn by the theme.
    $router->post('/impersonation/start/{id:number}', [ImpersonationController::class, 'start'])
        ->name('impersonation.start')
        ->permission(CorePermissions::USERS_IMPERSONATE);
    // No permission on the way back: whoever is inside an impersonated session must always be
    // able to get out of it, and the account they are browsing as holds nothing.
    $router->post('/impersonation/stop', [ImpersonationController::class, 'stop'])
        ->name('impersonation.stop');

    // Signing in through an external service. Core routes for the same reason: both login screens
    // draw the buttons, and the callback address is parameterised by the provider key so a module
    // adding a service needs no routes of its own.
    //
    // The fixed paths come first: `complete` matches the provider pattern too, and the matcher
    // answers with the first route that fits — declared the other way round, this screen would be
    // "start signing in with the service called complete".
    $router->get('/auth/complete', [ExternalAuthController::class, 'profileForm'])->name('auth.external.complete');
    $router->post('/auth/complete', [ExternalAuthController::class, 'completeProfile'])
        ->name('auth.external.complete.save');
    $router->get('/auth/{provider}', [ExternalAuthController::class, 'start'])
        ->name('auth.external.start')
        ->requirements(['provider' => '[a-z0-9_-]+']);
    // The provider posts or redirects here; it never carries our CSRF token, and the round trip
    // is guarded by state and PKCE instead.
    $router->map(['GET', 'POST'], '/auth/{provider}/callback', [ExternalAuthController::class, 'callback'])
        ->name('auth.external.callback')
        ->requirements(['provider' => '[a-z0-9_-]+'])
        ->withoutCsrf();
};
