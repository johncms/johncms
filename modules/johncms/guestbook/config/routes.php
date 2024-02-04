<?php

declare(strict_types=1);

use Johncms\Guestbook\Controllers\GuestbookController;
use Johncms\Router\RouteCollection;
use Johncms\Users\Middlewares\AuthorizedUserMiddleware;
use Johncms\Users\Middlewares\HasPermissionMiddleware;

return function (RouteCollection $router) {
    $router->get('/guestbook/', [GuestbookController::class, 'index'])->setName('guestbook.index');
    $router->post('/guestbook/', [GuestbookController::class, 'index']);

    $router->group(
        '/guestbook',
        function (RouteCollection $router) {
            $router->get('/ga/', [GuestbookController::class, 'switchGuestbookType'])->setName('guestbook.switch_type');

            $router->post('/upload_file/', [GuestbookController::class, 'loadFile'])
                ->addMiddleware(AuthorizedUserMiddleware::class)
                ->setName('guestbook.uploadFile');

            $router->get('/edit/{id:number}/', [GuestbookController::class, 'edit'])
                ->addMiddleware(new HasPermissionMiddleware('guestbook_delete_posts'))
                ->setName('guestbook.edit');
            $router->post('/edit/{id:number}/', [GuestbookController::class, 'edit'])
                ->addMiddleware(new HasPermissionMiddleware('guestbook_delete_posts'))
                ->setName('guestbook.edit.store');

            $router->get('/delpost/{id:number}/', [GuestbookController::class, 'delete'])
                ->addMiddleware(new HasPermissionMiddleware('guestbook_delete_posts'))
                ->setName('guestbook.delete');
            $router->post('/delpost/{id:number}/', [GuestbookController::class, 'delete'])
                ->addMiddleware(new HasPermissionMiddleware('guestbook_delete_posts'))
                ->setName('guestbook.delete.store');

            $router->get('/reply/{id:number}/', [GuestbookController::class, 'reply'])
                ->addMiddleware(new HasPermissionMiddleware('guestbook_delete_posts'))
                ->setName('guestbook.reply');
            $router->post('/reply/{id:number}/', [GuestbookController::class, 'reply'])
                ->addMiddleware(new HasPermissionMiddleware('guestbook_delete_posts'))
                ->setName('guestbook.reply.store');

            $router->get('/clean/', [GuestbookController::class, 'clean'])
                ->addMiddleware(new HasPermissionMiddleware('guestbook_clear'))
                ->setName('guestbook.clean');
            $router->post('/clean/', [GuestbookController::class, 'clean'])
                ->addMiddleware(new HasPermissionMiddleware('guestbook_clear'))
                ->setName('guestbook.clean.store');
        }
    );
};
