<?php

use Johncms\Forum\Controllers\ForumSectionsController;
use Johncms\Forum\Controllers\ForumTopicsController;
use Johncms\Forum\Controllers\LatestTopicsController;
use League\Route\Router;

return function (Router $router) {
    $router->get('/forum[/]', [ForumSectionsController::class, 'index'])->setName('forum.index');
    $router->get('/forum/unread[/]', [LatestTopicsController::class, 'unread'])->setName('forum.unread');

    // Sections, topic
    $router->get('/forum/{sectionName:slug}-{id:number}[/]', [ForumSectionsController::class, 'section'])->setName('forum.section');
    $router->get('/forum/t/{topicName:slug}-{id:number}[/]', [ForumTopicsController::class, 'showTopic'])->setName('forum.topic');

    // Write message
    $router->get('/forum/add-message/{topicId:number}[/]', [ForumTopicsController::class, 'addMessage'])->setName('forum.addMessage');
};
