<?php

use Johncms\Forum\Controllers\ForumFilesController;
use Johncms\Forum\Controllers\ForumMessagesController;
use Johncms\Forum\Controllers\ForumSectionsController;
use Johncms\Forum\Controllers\ForumTopicsController;
use Johncms\Forum\Controllers\LatestTopicsController;
use Johncms\Users\Middlewares\AuthorizedUserMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

return function (Router $router) {
    $router->get('/forum[/]', [ForumSectionsController::class, 'index'])->setName('forum.index');
    $router->get('/forum/unread[/]', [LatestTopicsController::class, 'unread'])->setName('forum.unread');

    // Sections, topic
    $router->get('/forum/{sectionName:slug}-{id:number}[/]', [ForumSectionsController::class, 'section'])->setName('forum.section');
    $router->get('/forum/t/{topicName:slug}-{id:number}[/]', [ForumTopicsController::class, 'showTopic'])->setName('forum.topic');

    $router->group('/forum', function (RouteGroup $route) {
        // Write message
        $route->get('/add-message/{topicId:number}[/]', [ForumMessagesController::class, 'addMessage'])->setName('forum.addMessage');
        $route->post('/add-message/{topicId:number}[/]', [ForumMessagesController::class, 'addMessage']);

        // Add file
        $route->get('/add-file/{messageId:number}[/]', [ForumFilesController::class, 'addFile'])->setName('forum.addFile');
        $route->post('/add-file/{messageId:number}[/]', [ForumFilesController::class, 'addFile']);
    })->lazyMiddleware(AuthorizedUserMiddleware::class);
};
