<?php

declare(strict_types=1);

use Johncms\Language\Controllers\LanguageController;
use Johncms\Router\RouteCollection;

return function (RouteCollection $router) {
    $router->map(['GET', 'POST'], '/language/', [LanguageController::class, 'index'])->setName('language.index');
};
