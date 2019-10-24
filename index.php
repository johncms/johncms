<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

const DEBUG = true;
const _IN_JOHNCMS = true;

require('system/bootstrap.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Psr\Http\Message\ServerRequestInterface $request */
$request = $container->get(Psr\Http\Message\ServerRequestInterface::class);

require 'system/routes.php';

$dispatcher = new FastRoute\Dispatcher\GroupCountBased(
    $container->get(FastRoute\RouteCollector::class)->getData()
);

$match = $dispatcher->dispatch(
    $request->getMethod(),
    $request->getUri()->getPath()
);

switch ($match[0]) {
    case FastRoute\Dispatcher::FOUND:
        if (is_callable($match[1])) {
            call_user_func_array($match[1], $match[2]);
        } else {
            include ROOT_PATH . $match[1];
        }
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        echo '405 Method Not Allowed';
        break;

    default:
        echo '404 Not Found';
}
