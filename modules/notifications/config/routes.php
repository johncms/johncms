<?php

declare(strict_types=1);

use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    if ($user->isValid()) {
        $router->map(['GET', 'POST'], '/notifications/{action}', 'modules/notifications/index.php')->name('notifications.index')->defaults(['action' => null]);
    }
};
