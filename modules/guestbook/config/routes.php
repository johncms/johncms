<?php

declare(strict_types=1);

use Johncms\Http\Middleware\RequireAuthMiddleware;
use Johncms\Modules\Guestbook\Application\Controllers\ClearGuestbookController;
use Johncms\Modules\Guestbook\Application\Controllers\DeleteEntryController;
use Johncms\Modules\Guestbook\Application\Controllers\EditEntryController;
use Johncms\Modules\Guestbook\Application\Controllers\GuestbookController;
use Johncms\Modules\Guestbook\Application\Controllers\ReplyController;
use Johncms\Modules\Guestbook\Application\Controllers\SwitchTypeController;
use Johncms\Modules\Guestbook\Application\Controllers\UploadFileController;
use Johncms\Modules\Guestbook\Application\Middlewares\GuestbookCleanAccessMiddleware;
use Johncms\Modules\Guestbook\Application\Middlewares\GuestbookEditAccessMiddleware;
use Johncms\Modules\Guestbook\Application\Middlewares\GuestbookReplyAccessMiddleware;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $router->map(['GET', 'POST'], '/guestbook', GuestbookController::class)->name('guestbook.index');
    $router->map(['GET', 'POST'], '/guestbook/ga', SwitchTypeController::class)->name('guestbook.switch_type');
    $router
        ->map(['GET', 'POST'], '/guestbook/edit', EditEntryController::class)
        ->name('guestbook.edit')
        ->addMiddleware(GuestbookEditAccessMiddleware::class);
    $router
        ->map(['GET', 'POST'], '/guestbook/delpost', DeleteEntryController::class)
        ->name('guestbook.delete')
        ->addMiddleware(GuestbookEditAccessMiddleware::class);
    $router
        ->map(['GET', 'POST'], '/guestbook/otvet', ReplyController::class)
        ->name('guestbook.reply')
        ->addMiddleware(GuestbookReplyAccessMiddleware::class);
    $router
        ->map(['GET', 'POST'], '/guestbook/clean', ClearGuestbookController::class)
        ->name('guestbook.clean')
        ->addMiddleware(GuestbookCleanAccessMiddleware::class);
    $router->map(['GET', 'POST'], '/guestbook/upload_file', UploadFileController::class)
        ->name('guestbook.upload_file')
        ->addMiddleware(RequireAuthMiddleware::class);
};
