<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Application;
use Johncms\Container\ContainerFactory;
use Johncms\Mail\EmailSender;

// If the system is not installed, redirect to the installer.
if (! is_file('config/autoload/database.local.php')) {
    header('Location: /install/');
    exit;
}

require 'system/bootstrap.php';

$container = ContainerFactory::getContainer();
di(PDO::class);
$application = new Application($container);
$application->run();

// If cron usage is disabled.
if (! USE_CRON && ! defined('_IN_JOHNADM')) {
    $cron_cache = CACHE_PATH . 'cron.cache';
    if (! file_exists($cron_cache) || filemtime($cron_cache) < (time() - 5)) {
        EmailSender::send();
        file_put_contents($cron_cache, time());
    }
}
