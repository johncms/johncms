<?php

declare(strict_types=1);

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
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    $router->map(['GET', 'POST'], '/guestbook', GuestbookController::class);
    $router->map(['GET', 'POST'], '/guestbook/ga', SwitchTypeController::class);
    $router
        ->map(['GET', 'POST'], '/guestbook/edit', EditEntryController::class)
        ->addMiddleware(GuestbookEditAccessMiddleware::class);
    $router
        ->map(['GET', 'POST'], '/guestbook/delpost', DeleteEntryController::class)
        ->addMiddleware(GuestbookEditAccessMiddleware::class);
    $router
        ->map(['GET', 'POST'], '/guestbook/otvet', ReplyController::class)
        ->addMiddleware(GuestbookReplyAccessMiddleware::class);
    $router
        ->map(['GET', 'POST'], '/guestbook/clean', ClearGuestbookController::class)
        ->addMiddleware(GuestbookCleanAccessMiddleware::class);
    if ($user->isValid()) {
        $router->map(['GET', 'POST'], '/guestbook/upload_file', UploadFileController::class);
    }
};
