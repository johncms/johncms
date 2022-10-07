<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Http\IpLogger;

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

// If there are no dependencies, we stop the script and displays an error
if (! is_file(__DIR__ . '/vendor/autoload.php')) {
    die('<h1>ERROR</h1><p>Missing dependencies</p>');
}

define('START_MEMORY', memory_get_usage());
define('START_TIME', microtime(true));

require __DIR__ . '/vendor/autoload.php';

defined('_IN_JOHNCMS') || die('Error: restricted access');

// Error handling
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
    ini_set('log_errors', 'On');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 'Off');
    ini_set('log_errors', 'Off');
}

if (! defined('CONSOLE_MODE') || CONSOLE_MODE === false) {
    header('X-Powered-CMS: JohnCMS');
    header('X-CMS-Version: ' . CMS_VERSION);
    di(IpLogger::class);

    /** @var PDO $db */
    //$db = $container->get(PDO::class);

    //(new BanIP())->checkBan();

    // System cleanup
    //new Johncms\Utility\Cleanup($db);
}



