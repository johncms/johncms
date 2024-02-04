<?php

declare(strict_types=1);

use Johncms\Forum\Controllers\CuratorsController;
use Johncms\Forum\Controllers\FilesController;
use Johncms\Forum\Controllers\MessagesController;
use Johncms\Forum\Controllers\OnlineController;
use Johncms\Forum\Controllers\SearchController;
use Johncms\Forum\Controllers\SectionsController;
use Johncms\Forum\Controllers\TopicsController;
use Johncms\Forum\Controllers\LatestTopicsController;
use Johncms\Forum\Controllers\PollController;
use Johncms\Forum\ForumPermissions;
use Johncms\Router\RouteCollection;
use Johncms\Users\Middlewares\AuthorizedUserMiddleware;
use Johncms\Users\Middlewares\HasPermissionMiddleware;

return function (RouteCollection $router) {
    $router->get('/forum/', [SectionsController::class, 'index'])->setName('forum.index');
    $router->get('/forum/latest/', [LatestTopicsController::class, 'latest'])->setName('forum.latest');

    // Sections, topic
    $router->get('/forum/{sectionName:slug}-{id:number}/', [SectionsController::class, 'show'])->setName('forum.section');
    $router->get('/forum/t/{topicName:slug}-{id:number}/', [TopicsController::class, 'show'])->setName('forum.topic');

    $router->get('/forum/search/', [SearchController::class, 'index'])->setName('forum.search');
    $router->post('/forum/search/', [SearchController::class, 'index']);

    $router->get('/forum/filter/{topicId:number}/', [TopicsController::class, 'filter'])->setName('forum.filter');
    $router->post('/forum/filter/{topicId:number}/', [TopicsController::class, 'filter']);

    $router->group('/forum', function (RouteCollection $route) {
        $route->get('/unread/', [LatestTopicsController::class, 'unread'])->setName('forum.unread');
        $route->get('/period/', [LatestTopicsController::class, 'period'])->setName('forum.period');
        $route->get('/mark-as-read/', [LatestTopicsController::class, 'markAsRead'])->setName('forum.markAsRead');

        $route->get('/online/users/', [OnlineController::class, 'allUsers'])->setName('forum.onlineUsers');
        $route->get('/online/guests/', [OnlineController::class, 'allGuests'])->setName('forum.onlineGuests');

        // Write message
        $route->get('/add-message/{topicId:number}/', [MessagesController::class, 'create'])->setName('forum.addMessage');
        $route->post('/add-message/{topicId:number}/', [MessagesController::class, 'create']);

        $route->get('/edit-post/{id:number}/', [MessagesController::class, 'edit'])->setName('forum.editMessage');
        $route->post('/edit-post/{id:number}/', [MessagesController::class, 'edit']);

        $route->get('/reply/{id:number}/', [MessagesController::class, 'reply'])->setName('forum.reply');
        $route->post('/reply/{id:number}/', [MessagesController::class, 'reply']);

        // Add file
        $route->get('/add-file/{messageId:number}/', [FilesController::class, 'add'])->setName('forum.addFile');
        $route->post('/add-file/{messageId:number}/', [FilesController::class, 'add']);

        $route->get('/create-topic/{sectionId:number}/', [TopicsController::class, 'create'])->setName('forum.newTopic');
        $route->post('/create-topic-store/{sectionId:number}/', [TopicsController::class, 'store'])->setName('forum.storeTopic');

        $route->get('/mass-delete/{topicId:number}/', [TopicsController::class, 'massDelete'])
            ->addMiddleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_POSTS))
            ->setName('forum.massDelete');
        $route->post('/mass-delete/{topicId:number}/', [TopicsController::class, 'massDelete'])
            ->addMiddleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_POSTS));

        $route->post('/vote/{topicId:number}/', [PollController::class, 'vote'])->setName('forum.vote');

        $route->get('/vote-users/{topicId:number}/', [PollController::class, 'users'])
            ->addMiddleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS))
            ->setName('forum.voteUsers');

        // Delete message
        $route->get('/delete-post/{id:number}/', [MessagesController::class, 'delete'])->setName('forum.deletePost');
        $route->post('/delete-post-confirm/{id:number}/', [MessagesController::class, 'confirmDelete'])->setName('forum.confirmDeletePost');

        $route->get('/restore-post/{id:number}/', [MessagesController::class, 'restore'])
            ->addMiddleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_POSTS))
            ->setName('forum.restorePost');
    })->addMiddleware(AuthorizedUserMiddleware::class);

    $router->group('/forum/topic', function (RouteCollection $route) {
        $route->get('/edit/{topicId:number}/', [TopicsController::class, 'edit'])->setName('forum.editTopic');
        $route->post('/edit-store/{topicId:number}/', [TopicsController::class, 'changeTopic'])->setName('forum.changeTopic');

        // Delete topic
        $route->get('/delete/{topicId:number}/', [TopicsController::class, 'delete'])->setName('forum.deleteTopic');
        $route->post('/delete-confirm/{topicId:number}/', [TopicsController::class, 'confirmDelete'])->setName('forum.confirmDelete');

        $route->get('/restore/{topicId:number}/', [TopicsController::class, 'restore'])->setName('forum.restoreTopic');
        $route->get('/close/{topicId:number}/', [TopicsController::class, 'close'])->setName('forum.closeTopic');
        $route->get('/open/{topicId:number}/', [TopicsController::class, 'open'])->setName('forum.openTopic');
        $route->get('/pin/{topicId:number}/', [TopicsController::class, 'pin'])->setName('forum.pinTopic');
        $route->get('/unpin/{topicId:number}/', [TopicsController::class, 'unpin'])->setName('forum.unpinTopic');

        $route->get('/move/{topicId:number}/', [TopicsController::class, 'move'])->setName('forum.moveTopic');
        $route->post('/move/{topicId:number}/', [TopicsController::class, 'confirmMove']);

        $route->get('/add-poll/{topicId:number}/', [PollController::class, 'add'])->setName('forum.addPoll');
        $route->post('/add-poll/{topicId:number}/', [PollController::class, 'add']);

        $route->get('/edit-poll/{topicId:number}/', [PollController::class, 'edit'])->setName('forum.editPoll');
        $route->post('/edit-poll/{topicId:number}/', [PollController::class, 'edit']);

        $route->get('/delete-poll/{topicId:number}/', [PollController::class, 'delete'])->setName('forum.deletePoll');
        $route->post('/delete-poll/{topicId:number}/', [PollController::class, 'delete']);

        $route->get('/curators/{topicId:number}/', [CuratorsController::class, 'index'])->setName('forum.curators');
        $route->post('/curators/{topicId:number}/', [CuratorsController::class, 'index']);
    })
        ->addMiddleware(new HasPermissionMiddleware(ForumPermissions::MANAGE_TOPICS));
};
