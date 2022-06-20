<?php

use Johncms\Admin\Controllers\System\DebugBarController;
use League\Route\Router;

return function (Router $router) {
    // Login routes
    $router->get('/_debugbar/open/', [DebugBarController::class, 'index'])->setName('debugBar.open');
};
