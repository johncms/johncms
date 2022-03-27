<?php

use Johncms\Forum\Controllers\ForumSections;
use League\Route\Router;

return function (Router $router) {
    $router->get('/forum[/]', [ForumSections::class, 'index'])->setName('forum.index');
};
