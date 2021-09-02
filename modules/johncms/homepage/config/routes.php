<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

return function (\League\Route\Router $router) {
    $router->get('/', [\Johncms\Homepage\Controllers\HomepageController::class, 'index'])->setName('homepage.index');
};
