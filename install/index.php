<?php

declare(strict_types=1);

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Install\Checker;
use Install\Router;

const JOHNCMS = '9.2.0';

session_name('SESID');
session_start();

// Load files for install
require __DIR__ . '/../system/vendor/autoload.php';
require __DIR__ . '/src/vendor/Parsedown.php';
require __DIR__ . '/src/Checker.php';
require __DIR__ . '/src/Render.php';
require __DIR__ . '/src/Installer.php';
require __DIR__ . '/src/Controller.php';
require __DIR__ . '/src/Router.php';

$router = new Router();
$checker = new Checker($router->act);

$reports = $checker->checkAll();
$checker->renderCheckAll($reports);

$router->run();
