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

require 'system/bootstrap.php';

$container = Johncms\System\Container\Factory::getContainer();
$dispatcher = new GroupCountBased($container->get(RouteCollector::class)->getData());

$match = $dispatcher->dispatch(
    $_SERVER['REQUEST_METHOD'],
    (function () {
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

        if (is_callable($match[1])) {
            call_user_func_array($match[1], $match[2]);
        } else {
            include ROOT_PATH . $match[1];
        }
        break;

    case Dispatcher::METHOD_NOT_ALLOWED:
        echo '405 Method Not Allowed';
        break;

    default:
        pageNotFound();
}
