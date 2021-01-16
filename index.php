<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use Johncms\Controller\AbstractController;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Mail\EmailSender;

// If the system is not installed, redirect to the installer.
if (! is_file('config/autoload/database.local.php')) {
    header('Location: /install/');
    exit;
}

require 'system/bootstrap.php';

$container = Johncms\System\Container\Factory::getContainer();
$dispatcher = new GroupCountBased($container->get(RouteCollector::class)->getData());

$match = $dispatcher->dispatch(
    $_SERVER['REQUEST_METHOD'],
    (static function () {
        $uri = $_SERVER['REQUEST_URI'];
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        return rawurldecode($uri);
    })()
);

switch ($match[0]) {
    case Dispatcher::FOUND:
        // Register the location of the visitor on the site
        new Johncms\System\Users\UserStat($container);
        $container->setService('route', $match[2]);
        try {
            if (
                is_array($match[1]) &&
                class_exists($match[1][0]) &&
                is_subclass_of($match[1][0], AbstractController::class)
            ) {
                echo (new $match[1][0]())->runAction($match[1][1], $match[2]);
            } else {
                include ROOT_PATH . $match[1];
            }
        } catch (PageNotFoundException $exception) {
            pageNotFound($exception->getTemplate(), $exception->getTitle(), $exception->getMessage());
        }
        break;

    case Dispatcher::METHOD_NOT_ALLOWED:
        echo '405 Method Not Allowed';
        break;

    default:
        pageNotFound();
}

// If cron usage is disabled.
if (! USE_CRON && ! defined('_IN_JOHNADM')) {
    $cron_cache = CACHE_PATH . 'cron.cache';
    if (! file_exists($cron_cache) || filemtime($cron_cache) < (time() - 5)) {
        EmailSender::send();
        file_put_contents($cron_cache, time());
    }
}
