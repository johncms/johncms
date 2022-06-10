<?php

use Johncms\Forum\Controllers\ForumFilesController;
use Johncms\Forum\Controllers\ForumMessagesController;
use Johncms\Forum\Controllers\ForumSectionsController;
use Johncms\Forum\Controllers\ForumTopicsController;
use Johncms\Forum\Controllers\LatestTopicsController;
use Johncms\Forum\Controllers\PollController;
use Johncms\Forum\ForumPermissions;
use Johncms\Users\Middlewares\AuthorizedUserMiddleware;
use Johncms\Users\Middlewares\HasPermissionMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

return function (Router $router) {
    $router->get('/forum[/]', [ForumSectionsController::class, 'index'])->setName('forum.index');
    $router->get('/forum/unread[/]', [LatestTopicsController::class, 'unread'])->setName('forum.unread');

    // Sections, topic
    $router->get('/forum/{sectionName:slug}-{id:number}[/]', [ForumSectionsController::class, 'show'])->setName('forum.section');
    $router->get('/forum/t/{topicName:slug}-{id:number}[/]', [ForumTopicsController::class, 'show'])->setName('forum.topic');

    $router->group('/forum', function (RouteGroup $route) {
        // Write message
        $route->get('/add-message/{topicId:number}[/]', [ForumMessagesController::class, 'create'])->setName('forum.addMessage');
        $route->post('/add-message/{topicId:number}[/]', [ForumMessagesController::class, 'create']);

        // Add file
        $route->get('/add-file/{messageId:number}[/]', [ForumFilesController::class, 'add'])->setName('forum.addFile');
        $route->post('/add-file/{messageId:number}[/]', [ForumFilesController::class, 'add']);

        $route->get('/create-topic/{sectionId:number}[/]', [ForumTopicsController::class, 'create'])->setName('forum.newTopic');
        $route->post('/create-topic-store/{sectionId:number}[/]', [ForumTopicsController::class, 'store'])->setName('forum.storeTopic');

        $route->get('/edit-topic/{topicId:number}[/]', [ForumTopicsController::class, 'edit'])->setName('forum.editTopic');
        $route->post('/edit-topic-store/{topicId:number}[/]', [ForumTopicsController::class, 'changeTopic'])->setName('forum.changeTopic');

        // Delete topic
        $route->get('/delete-topic/{topicId:number}[/]', [ForumTopicsController::class, 'delete'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.deleteTopic');
        $route->post('/delete-topic-confirm/{topicId:number}[/]', [ForumTopicsController::class, 'confirmDelete'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.confirmDelete');

        $route->get('/restore-topic/{topicId:number}[/]', [ForumTopicsController::class, 'restore'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.restoreTopic');

        $route->get('/close-topic/{topicId:number}[/]', [ForumTopicsController::class, 'close'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.closeTopic');

        $route->get('/open-topic/{topicId:number}[/]', [ForumTopicsController::class, 'open'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.openTopic');

        $route->get('/pin-topic/{topicId:number}[/]', [ForumTopicsController::class, 'pin'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.pinTopic');

        $route->get('/unpin-topic/{topicId:number}[/]', [ForumTopicsController::class, 'unpin'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.unpinTopic');

        $route->get('/move-topic/{topicId:number}[/]', [ForumTopicsController::class, 'move'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.moveTopic');
        $route->post('/move-topic/{topicId:number}[/]', [ForumTopicsController::class, 'confirmMove'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.confirmMoveTopic');

        $route->get('/add-poll/{topicId:number}[/]', [PollController::class, 'add'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.addPoll');
        $route->post('/add-poll/{topicId:number}[/]', [PollController::class, 'add'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS));

        $route->get('/edit-poll/{topicId:number}[/]', [PollController::class, 'edit'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.editPoll');
        $route->post('/edit-poll/{topicId:number}[/]', [PollController::class, 'edit'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS));

        $route->get('/delete-poll/{topicId:number}[/]', [PollController::class, 'delete'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.deletePoll');
        $route->post('/delete-poll/{topicId:number}[/]', [PollController::class, 'delete'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS));

        $route->post('/vote/{topicId:number}[/]', [PollController::class, 'vote'])->setName('forum.vote');

        // Delete message
        $route->get('/delete-post/{id:number}[/]', [ForumMessagesController::class, 'delete'])->setName('forum.deletePost');
        $route->post('/delete-post-confirm/{id:number}[/]', [ForumMessagesController::class, 'confirmDelete'])->setName('forum.confirmDeletePost');
    })->lazyMiddleware(AuthorizedUserMiddleware::class);
};
