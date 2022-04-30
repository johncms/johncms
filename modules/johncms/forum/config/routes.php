<?php

use Johncms\Forum\Controllers\ForumFilesController;
use Johncms\Forum\Controllers\ForumMessagesController;
use Johncms\Forum\Controllers\ForumSectionsController;
use Johncms\Forum\Controllers\ForumTopicsController;
use Johncms\Forum\Controllers\LatestTopicsController;
use Johncms\Forum\ForumPermissions;
use Johncms\Users\Middlewares\AuthorizedUserMiddleware;
use Johncms\Users\Middlewares\HasPermissionMiddleware;
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

        $route->get('/create-topic/{sectionId:number}[/]', [ForumTopicsController::class, 'newTopic'])->setName('forum.newTopic');
        $route->post('/create-topic-store/{sectionId:number}[/]', [ForumTopicsController::class, 'storeTopic'])->setName('forum.storeTopic');

        // Delete topic
        $route->get('/delete-topic/{topicId:number}[/]', [ForumTopicsController::class, 'deleteTopic'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.deleteTopic');
        $route->post('/delete-topic-confirm/{topicId:number}[/]', [ForumTopicsController::class, 'confirmDelete'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.confirmDelete');

        // Delete message
        $route->get('/delete-post/{id:number}[/]', [ForumMessagesController::class, 'deleteMessage'])->setName('forum.deletePost');
        $route->post('/delete-post-confirm/{id:number}[/]', [ForumMessagesController::class, 'confirmDelete'])->setName('forum.confirmDeletePost');
    })->lazyMiddleware(AuthorizedUserMiddleware::class);
};
