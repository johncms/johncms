<?php

use Johncms\Forum\Controllers\CuratorsController;
use Johncms\Forum\Controllers\FilesController;
use Johncms\Forum\Controllers\MessagesController;
use Johncms\Forum\Controllers\SectionsController;
use Johncms\Forum\Controllers\TopicsController;
use Johncms\Forum\Controllers\LatestTopicsController;
use Johncms\Forum\Controllers\PollController;
use Johncms\Forum\ForumPermissions;
use Johncms\Users\Middlewares\AuthorizedUserMiddleware;
use Johncms\Users\Middlewares\HasPermissionMiddleware;
use League\Route\RouteGroup;
use League\Route\Router;

return function (Router $router) {
    $router->get('/forum[/]', [SectionsController::class, 'index'])->setName('forum.index');
    $router->get('/forum/unread[/]', [LatestTopicsController::class, 'unread'])->setName('forum.unread');

    // Sections, topic
    $router->get('/forum/{sectionName:slug}-{id:number}[/]', [SectionsController::class, 'show'])->setName('forum.section');
    $router->get('/forum/t/{topicName:slug}-{id:number}[/]', [TopicsController::class, 'show'])->setName('forum.topic');

    $router->group('/forum', function (RouteGroup $route) {
        // Write message
        $route->get('/add-message/{topicId:number}[/]', [MessagesController::class, 'create'])->setName('forum.addMessage');
        $route->post('/add-message/{topicId:number}[/]', [MessagesController::class, 'create']);

        $route->get('/edit-post/{id:number}[/]', [MessagesController::class, 'edit'])->setName('forum.editMessage');
        $route->post('/edit-post/{id:number}[/]', [MessagesController::class, 'edit']);

        // Add file
        $route->get('/add-file/{messageId:number}[/]', [FilesController::class, 'add'])->setName('forum.addFile');
        $route->post('/add-file/{messageId:number}[/]', [FilesController::class, 'add']);

        $route->get('/create-topic/{sectionId:number}[/]', [TopicsController::class, 'create'])->setName('forum.newTopic');
        $route->post('/create-topic-store/{sectionId:number}[/]', [TopicsController::class, 'store'])->setName('forum.storeTopic');

        $route->get('/edit-topic/{topicId:number}[/]', [TopicsController::class, 'edit'])->setName('forum.editTopic');
        $route->post('/edit-topic-store/{topicId:number}[/]', [TopicsController::class, 'changeTopic'])->setName('forum.changeTopic');

        // Delete topic
        $route->get('/delete-topic/{topicId:number}[/]', [TopicsController::class, 'delete'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.deleteTopic');
        $route->post('/delete-topic-confirm/{topicId:number}[/]', [TopicsController::class, 'confirmDelete'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.confirmDelete');

        $route->get('/restore-topic/{topicId:number}[/]', [TopicsController::class, 'restore'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.restoreTopic');

        $route->get('/close-topic/{topicId:number}[/]', [TopicsController::class, 'close'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.closeTopic');

        $route->get('/open-topic/{topicId:number}[/]', [TopicsController::class, 'open'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.openTopic');

        $route->get('/pin-topic/{topicId:number}[/]', [TopicsController::class, 'pin'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.pinTopic');

        $route->get('/unpin-topic/{topicId:number}[/]', [TopicsController::class, 'unpin'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.unpinTopic');

        $route->get('/move-topic/{topicId:number}[/]', [TopicsController::class, 'move'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.moveTopic');
        $route->post('/move-topic/{topicId:number}[/]', [TopicsController::class, 'confirmMove'])
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

        $route->get('/curators/{topicId:number}[/]', [CuratorsController::class, 'index'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.curators');
        $route->post('/curators/{topicId:number}[/]', [CuratorsController::class, 'index'])
            ->middleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS));

        $route->post('/vote/{topicId:number}[/]', [PollController::class, 'vote'])->setName('forum.vote');

        $route->get('/vote-users/{topicId:number}[/]', [PollController::class, 'users'])->setName('forum.voteUsers');

        // Delete message
        $route->get('/delete-post/{id:number}[/]', [MessagesController::class, 'delete'])->setName('forum.deletePost');
        $route->post('/delete-post-confirm/{id:number}[/]', [MessagesController::class, 'confirmDelete'])->setName('forum.confirmDeletePost');
    })->lazyMiddleware(AuthorizedUserMiddleware::class);
};
