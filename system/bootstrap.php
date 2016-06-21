<?php
/*
 * mobiCMS Content Management System (http://mobicms.net)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

define('START_MEMORY', memory_get_usage());
define('START_TIME', microtime(true));

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('CONFIG_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR);

//require __DIR__ . '/vendor/autoload.php';
