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
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Mail\EmailSender;

// If the system is not installed, redirect to the installer.
if (! is_file('config/autoload/database.local.php')) {
    header('Location: /install/');
    exit;
}

require 'system/bootstrap.php';

$container = \Johncms\Container\PSRContainerFactory::getContainer();
$logger = $container->get(\Psr\Log\LoggerInterface::class);
(new \Johncms\Logs\GlobalErrorHandler(
    logger:    $logger,
    container: $container
))->registerHandlers();
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

        // Set the current route parameters to the request object
        $container->get(\Johncms\System\Http\Request::class)->setCurrentRouteParams($match[2]);
        try {
            $handler = $match[1];
            $vars = $match[2];

            if (
                is_array($handler)
                && class_exists($handler[0])
            ) {
                echo $container
                    ->get($handler[0])
                    ->runAction($handler[1], $vars);

                break;
            }

            // Invokable controller
            if (
                is_string($handler)
                && class_exists($handler)
                && method_exists($handler, '__invoke')
            ) {
                echo $container->get($handler)($vars);

                break;
            }

            // Legacy include
            if (is_string($handler)) {
                include ROOT_PATH . $handler;
                break;
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
