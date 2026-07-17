<?php

declare(strict_types=1);

use Johncms\Exceptions\PageNotFoundException;
use Johncms\Mail\EmailSender;
use Johncms\Router\MiddlewareDispatcher;
use Johncms\Router\RouteMatchResult;
use Johncms\Router\SymfonyRouteMatcher;
use Johncms\System\Http\Request;

// Paths are resolved from __DIR__: both lines run before the constants are defined.
// If the system is not installed, redirect to the installer.
if (! is_file(dirname(__DIR__) . '/config/autoload/database.local.php')) {
    header('Location: /install/');
    exit;
}

require dirname(__DIR__) . '/system/bootstrap.php';

$container = \Johncms\Container\PSRContainerFactory::getContainer();
$logger = $container->get(\Psr\Log\LoggerInterface::class);
(new \Johncms\Logs\GlobalErrorHandler(
    logger:    $logger,
    container: $container
))->registerHandlers();
$uri = (static function () {
    $uri = $_SERVER['REQUEST_URI'];
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }

    $uri = rawurldecode($uri);
    if ($uri !== '/') {
        $uri = rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }
    }

    if ($uri === '/forum/index.php' || $uri === '/forum/index.php/') {
        return '/forum';
    }

    return $uri;
})();
$match = $container->get(SymfonyRouteMatcher::class)->dispatch($_SERVER['REQUEST_METHOD'], $uri);

switch ($match->status) {
    case RouteMatchResult::FOUND:
        // Register the location of the visitor on the site
        new Johncms\System\Users\UserStat($container);

        // Set the current route parameters to the request object
        $request = $container->get(\Johncms\System\Http\Request::class);
        $request->setCurrentRouteParams($match->params);
        $invoker = $container->get(\Johncms\Http\Controller\ActionInvoker::class);
        $middlewareDispatcher = $container->get(MiddlewareDispatcher::class);
        try {
            $handler = $match->handler;
            $vars = $match->params;
            $result = $middlewareDispatcher->dispatch(
                request: $request,
                middlewares: $match->middlewares,
                handler: static function (Request $request) use ($container, $handler, $invoker, $vars): mixed {
                    if (
                        is_array($handler)
                        && class_exists($handler[0])
                    ) {
                        $controller = $container->get($handler[0]);
                        return $invoker->invoke(
                            controller:  $controller,
                            method:      $handler[1],
                            routeParams: $vars,
                        );
                    }

                    // Invokable controller
                    if (
                        is_string($handler)
                        && class_exists($handler)
                        && method_exists($handler, '__invoke')
                    ) {
                        $controller = $container->get($handler);
                        return $invoker->invoke(
                            controller:  $controller,
                            method:      '__invoke',
                            routeParams: $vars,
                        );
                    }

                    // Legacy include
                    if (is_string($handler)) {
                        include ROOT_PATH . $handler;
                        return null;
                    }

                    return null;
                },
            );

            if ($result !== null) {
                echo $result;
            }
        } catch (PageNotFoundException $exception) {
            pageNotFound($exception->getTemplate(), $exception->getTitle(), $exception->getMessage());
        }
        break;

    case RouteMatchResult::METHOD_NOT_ALLOWED:
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
