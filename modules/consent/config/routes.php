<?php

declare(strict_types=1);

use Johncms\Modules\Admin\Application\Middlewares\AdminAccessMiddleware;
use Johncms\Modules\Consent\Application\Controllers\Admin\ConsentDeleteController;
use Johncms\Modules\Consent\Application\Controllers\Admin\ConsentEditController;
use Johncms\Modules\Consent\Application\Controllers\Admin\ConsentListController;
use Johncms\Modules\Consent\Application\Controllers\Admin\ConsentLogController;
use Johncms\Modules\Consent\Application\Controllers\ConsentViewController;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    // Public: read-only view of a consent text (target of the registration checkbox link).
    $router->get('/consent/{id:number}', ConsentViewController::class)->name('consent.view');

    // Admin: consents directory + acceptance log.
    $router->group('', function (RouteCollection $r): void {
        $r->get('/admin/consents', ConsentListController::class)->name('admin.consents');
        $r->get('/admin/consents/log', ConsentLogController::class)->name('admin.consents.log');
        $r->map(['GET', 'POST'], '/admin/consents/create', ConsentEditController::class)->name('admin.consents.create');
        $r->map(['GET', 'POST'], '/admin/consents/{id:number}/edit', ConsentEditController::class)->name('admin.consents.edit');
        $r->map(['GET', 'POST'], '/admin/consents/{id:number}/delete', ConsentDeleteController::class)->name('admin.consents.delete');
    })->addMiddleware(AdminAccessMiddleware::class);
};
