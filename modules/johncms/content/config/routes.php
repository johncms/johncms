<?php

declare(strict_types=1);

use Johncms\Content\Controllers\Admin\ContentAdminController;
use League\Route\RouteGroup;
use League\Route\Router;

return function (Router $router) {
    $router->group('/admin/content', function (RouteGroup $route) {
        $route->get('/', [ContentAdminController::class, 'index'])->setName('content.admin.index');
        $route->get('/types/create[/]', [ContentAdminController::class, 'createContentType'])->setName('content.admin.createContentType');
        $route->post('/types/create[/]', [ContentAdminController::class, 'createContentType']);
        $route->get('/types/delete/{id:number}[/]', [ContentAdminController::class, 'delete'])->setName('content.admin.delete');
        $route->post('/types/delete/{id:number}[/]', [ContentAdminController::class, 'delete']);
    });
};
