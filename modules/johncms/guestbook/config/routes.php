<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Guestbook\Controllers\GuestbookController;
use Johncms\Users\Middlewares\AuthorizedUserMiddleware;
use Johncms\Users\Middlewares\HasPermissionMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

/**
 * @psalm-suppress UndefinedInterfaceMethod
 */
return function (Router $router) {
    $router->get('/guestbook[/]', [GuestbookController::class, 'index'])->setName('guestbook.index');
    $router->post('/guestbook[/]', [GuestbookController::class, 'index']);

    $router->group(
        '/guestbook',
        function (RouteGroup $router) {
            $router->get('/ga[/]', [GuestbookController::class, 'switchGuestbookType'])->setName('guestbook.switch_type');

            $router->post('/upload_file/', [GuestbookController::class, 'loadFile'])
                ->lazyMiddleware(AuthorizedUserMiddleware::class)
                ->setName('guestbook.upload_file');

            $router->get('/edit[/]', [GuestbookController::class, 'edit'])
                ->middleware(new HasPermissionMiddleware('guestbook_delete_posts'))
                ->setName('guestbook.edit');
            $router->post('/edit[/]', [GuestbookController::class, 'edit'])
                ->middleware(new HasPermissionMiddleware('guestbook_delete_posts'));

            $router->get('/delpost[/]', [GuestbookController::class, 'delete'])
                ->middleware(new HasPermissionMiddleware('guestbook_delete_posts'))
                ->setName('guestbook.delete');
            $router->post('/delpost[/]', [GuestbookController::class, 'delete'])
                ->middleware(new HasPermissionMiddleware('guestbook_delete_posts'));

            $router->get('/otvet[/]', [GuestbookController::class, 'reply'])
                ->middleware(new HasPermissionMiddleware('guestbook_delete_posts'))
                ->setName('guestbook.reply');
            $router->post('/otvet[/]', [GuestbookController::class, 'reply'])
                ->middleware(new HasPermissionMiddleware('guestbook_delete_posts'));

            $router->get('/clean[/]', [GuestbookController::class, 'clean'])
                ->middleware(new HasPermissionMiddleware('guestbook_clear'))
                ->setName('guestbook.clean');
            $router->post('/clean[/]', [GuestbookController::class, 'clean'])
                ->middleware(new HasPermissionMiddleware('guestbook_clear'));
        }
    );
};
