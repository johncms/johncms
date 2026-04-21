<?php

declare(strict_types=1);

use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    $router->map(['GET', 'POST'], '/online/{action}', 'modules/online/index.php')->name('online.index')->defaults(['action' => null]);
};
