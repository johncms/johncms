<?php

declare(strict_types=1);

use Johncms\Modules\Mail\Application\Controllers\AddContactController;
use Johncms\Modules\Mail\Application\Controllers\BlocklistIndexController;
use Johncms\Modules\Mail\Application\Controllers\BlockUserController;
use Johncms\Modules\Mail\Application\Controllers\ClearConversationController;
use Johncms\Modules\Mail\Application\Controllers\ContactController;
use Johncms\Modules\Mail\Application\Controllers\DeleteContactController;
use Johncms\Modules\Mail\Application\Controllers\DeleteMessageController;
use Johncms\Modules\Mail\Application\Controllers\DownloadFileController;
use Johncms\Modules\Mail\Application\Controllers\FilesController;
use Johncms\Modules\Mail\Application\Controllers\IncomingConversationsController;
use Johncms\Modules\Mail\Application\Controllers\OutgoingConversationsController;
use Johncms\Modules\Mail\Application\Controllers\UnblockUserController;
use Johncms\Modules\Mail\Application\Controllers\WriteController;
use Johncms\Modules\Mail\Application\Middlewares\AuthorizedUserMiddleware;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    // All routes require an authenticated user
    $mailGroup = $router->group('', function (RouteCollection $r): void {
        $r->get('/mail', ContactController::class)->name('mail.contacts');
        $r->get('/mail/incoming', IncomingConversationsController::class)->name('mail.incoming');
        $r->get('/mail/outgoing', OutgoingConversationsController::class)->name('mail.outgoing');
        $r->get('/mail/add/{id:number}', [AddContactController::class, 'confirm'])->name('mail.add.confirm');
        $r->post('/mail/add/{id:number}', [AddContactController::class, 'add'])->name('mail.add');
        $r->get('/mail/blocklist', BlocklistIndexController::class)->name('mail.blocklist');
        $r->map(['GET', 'POST'], '/mail/block/{id:number}', BlockUserController::class)->name('mail.block');
        $r->map(['GET', 'POST'], '/mail/unblock/{id:number}', UnblockUserController::class)->name('mail.unblock');
        $r->get('/mail/delete/{id:number}', [DeleteMessageController::class, 'confirm'])->name('mail.delete.confirm');
        $r->post('/mail/delete/{id:number}', [DeleteMessageController::class, 'delete'])->name('mail.delete');
        $r->get('/mail/delete-contact/{id:number}', [DeleteContactController::class, 'confirm'])->name('mail.delete-contact.confirm');
        $r->post('/mail/delete-contact/{id:number}', [DeleteContactController::class, 'delete'])->name('mail.delete-contact');
        $r->get('/mail/load/{id:number}', DownloadFileController::class)->name('mail.load');
        $r->get('/mail/files', FilesController::class)->name('mail.files');
        $r->get('/mail/write/{id:number}', [WriteController::class, 'conversation'])->name('mail.write');
        $r->post('/mail/write/{id:number}', [WriteController::class, 'send'])->name('mail.write.send');
        $r->get('/mail/clear/{id:number}', [ClearConversationController::class, 'confirm'])->name('mail.clear.confirm');
        $r->post('/mail/clear/{id:number}', [ClearConversationController::class, 'clear'])->name('mail.clear');
    });
    $mailGroup->addMiddleware(AuthorizedUserMiddleware::class);
};
