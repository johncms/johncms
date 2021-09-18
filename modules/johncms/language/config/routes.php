<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Language\Controllers\LanguageController;
use League\Route\Router;

return function (Router $router) {
    $router->get('/language[/]', [LanguageController::class, 'index'])->setName('language.index');
};
