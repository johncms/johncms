<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Guestbook\Controllers\GuestbookController;
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
            // TODO: Delete unused routes and add middlewares for check rights
            $router->get('/ga[/]', [GuestbookController::class, 'switchGuestbookType'])->setName('guestbook.switch_type');
            $router->post('/upload_file/', [GuestbookController::class, 'loadFile'])->setName('guestbook.upload_file');
            $router->get('/edit[/]', [GuestbookController::class, 'edit'])->setName('guestbook.edit');
            $router->post('/edit[/]', [GuestbookController::class, 'edit']);
            $router->get('/delpost[/]', [GuestbookController::class, 'delete'])->setName('guestbook.delete');
            $router->post('/delpost[/]', [GuestbookController::class, 'delete']);
            $router->get('/otvet[/]', [GuestbookController::class, 'reply'])->setName('guestbook.reply');
            $router->post('/otvet[/]', [GuestbookController::class, 'reply']);
            $router->get('/clean[/]', [GuestbookController::class, 'clean'])->setName('guestbook.clean');
            $router->post('/clean[/]', [GuestbookController::class, 'clean']);
        }
    );
};
