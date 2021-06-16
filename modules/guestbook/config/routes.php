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

return function (Router $router) {
    $router->get('/guestbook[/]', [GuestbookController::class, 'index']);
    $router->post('/guestbook[/]', [GuestbookController::class, 'index']);

    $router->group(
        '/guestbook',
        function (RouteGroup $router) {
            // TODO: Delete unused routes and add middlewares for check rights
            $router->get('/ga[/]', [GuestbookController::class, 'switchGuestbookType']);
            $router->post('/ga[/]', [GuestbookController::class, 'switchGuestbookType']);
            $router->post('/upload_file[/]', [GuestbookController::class, 'loadFile']);
            $router->get('/edit[/]', [GuestbookController::class, 'edit']);
            $router->post('/edit[/]', [GuestbookController::class, 'edit']);
            $router->get('/delpost[/]', [GuestbookController::class, 'delete']);
            $router->post('/delpost[/]', [GuestbookController::class, 'delete']);
            $router->get('/otvet[/]', [GuestbookController::class, 'reply']);
            $router->post('/otvet[/]', [GuestbookController::class, 'reply']);
            $router->get('/clean[/]', [GuestbookController::class, 'clean']);
            $router->post('/clean[/]', [GuestbookController::class, 'clean']);
        }
    );
};
