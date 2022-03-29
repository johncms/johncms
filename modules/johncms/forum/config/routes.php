<?php

use Johncms\Forum\Controllers\ForumSectionsController;
use Johncms\Forum\Controllers\LatestTopicsController;
use League\Route\Router;

return function (Router $router) {
    $router->get('/forum[/]', [ForumSectionsController::class, 'index'])->setName('forum.index');
    $router->get('/forum/unread[/]', [LatestTopicsController::class, 'unread'])->setName('forum.unread');
};
