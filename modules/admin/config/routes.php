<?php

declare(strict_types=1);

use Johncms\Modules\Admin\Application\Controllers\System\SystemCheckController;
use Johncms\Modules\Admin\Application\Controllers\Users\UsersController;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    $router->map(['GET', 'POST'], '/admin/login', [UsersController::class, 'login']);
    if ($user->rights >= 6 && $user->isValid()) {
        $router->map(['GET', 'POST'], '/admin/system_check', [SystemCheckController::class, 'index']);
    }
    $router->map(['GET', 'POST'], '/admin/{action}', 'modules/admin/index.php')->defaults(['action' => null]);
};
