<?php

declare(strict_types=1);

use Johncms\Modules\Admin\Application\Middlewares\AdminAccessMiddleware;
use Johncms\Modules\Contacts\Application\Controllers\Admin\ContactMessageDeleteController;
use Johncms\Modules\Contacts\Application\Controllers\Admin\ContactMessageListController;
use Johncms\Modules\Contacts\Application\Controllers\Admin\ContactMessageViewController;
use Johncms\Modules\Contacts\Application\Controllers\Admin\ContactSettingsController;
use Johncms\Modules\Contacts\Application\Controllers\ContactsController;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    // Public: contact information and the feedback form.
    $router->map(['GET', 'POST'], '/contacts', ContactsController::class)->name('contacts');

    // Admin: contact settings and the received messages.
    $router->group('', function (RouteCollection $r): void {
        $r->get('/admin/contacts', [ContactSettingsController::class, 'form'])->name('admin.contacts');
        $r->post('/admin/contacts', [ContactSettingsController::class, 'save'])->name('admin.contacts.save');
        $r->get('/admin/contacts/messages', ContactMessageListController::class)->name('admin.contacts.messages');
        $r->map(['GET', 'POST'], '/admin/contacts/messages/{id:number}', ContactMessageViewController::class)
            ->name('admin.contacts.messages.view');
        $r->map(['GET', 'POST'], '/admin/contacts/messages/{id:number}/delete', ContactMessageDeleteController::class)
            ->name('admin.contacts.messages.delete');
    })->addMiddleware(AdminAccessMiddleware::class);
};
