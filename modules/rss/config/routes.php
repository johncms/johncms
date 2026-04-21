<?php

declare(strict_types=1);

use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    $router->get('/rss', 'modules/rss/index.php');
};
