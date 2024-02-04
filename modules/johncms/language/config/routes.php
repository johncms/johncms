<?php

declare(strict_types=1);

use Johncms\Language\Controllers\LanguageController;
use Johncms\Router\RouteCollection;

return function (RouteCollection $router) {
    $router->get('/language[/]', [LanguageController::class, 'index'])->setName('language.index');
    $router->post('/language[/]', [LanguageController::class, 'index']);
};
