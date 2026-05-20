<?php

declare(strict_types=1);

use Johncms\Modules\Language\Application\Controllers\LanguageController;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $router->map(['GET', 'POST'], '/language', LanguageController::class)->name('language.index');
};
