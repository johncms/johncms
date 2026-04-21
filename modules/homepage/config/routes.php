<?php

declare(strict_types=1);

use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    $router->get('/', \Johncms\Modules\Homepage\Controllers\HomepageController::class);
};
