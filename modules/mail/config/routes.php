<?php

declare(strict_types=1);

use Johncms\Modules\Mail\Application\Controllers\AddContactController;
use Johncms\Modules\Mail\Application\Controllers\BlocklistIndexController;
use Johncms\Modules\Mail\Application\Controllers\BlockUserController;
use Johncms\Modules\Mail\Application\Controllers\ContactController;
use Johncms\Modules\Mail\Application\Controllers\DeleteContactController;
use Johncms\Modules\Mail\Application\Controllers\DeleteMessageController;
use Johncms\Modules\Mail\Application\Controllers\UnblockUserController;
use Johncms\Modules\Mail\Application\Middlewares\AuthorizedUserMiddleware;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    // Old legacy route (keep for compatibility during transition)
    $router->map(['GET', 'POST'], '/mail', 'modules/mail/index.php')->name('mail.index');

    // New routes (require authenticated user)
    $mailGroup = $router->group('', function (RouteCollection $r): void {
        $r->get('/mail/', ContactController::class)->name('mail.contacts');
        $r->get('/mail/add/{id:number}', [AddContactController::class, 'confirm'])->name('mail.add.confirm');
        $r->post('/mail/add/{id:number}', [AddContactController::class, 'add'])->name('mail.add');
        $r->get('/mail/blocklist', BlocklistIndexController::class)->name('mail.blocklist');
        $r->map(['GET', 'POST'], '/mail/block/{id:number}', BlockUserController::class)->name('mail.block');
        $r->map(['GET', 'POST'], '/mail/unblock/{id:number}', UnblockUserController::class)->name('mail.unblock');
        $r->get('/mail/delete/{id:number}', [DeleteMessageController::class, 'confirm'])->name('mail.delete.confirm');
        $r->post('/mail/delete/{id:number}', [DeleteMessageController::class, 'delete'])->name('mail.delete');
        $r->get('/mail/delete-contact/{id:number}', [DeleteContactController::class, 'confirm'])->name('mail.delete-contact.confirm');
        $r->post('/mail/delete-contact/{id:number}', [DeleteContactController::class, 'delete'])->name('mail.delete-contact');
    });
    $mailGroup->addMiddleware(AuthorizedUserMiddleware::class);
};
