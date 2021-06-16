<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Router;

use Johncms\Modules\Modules;
use Johncms\System\Router\Strategy\ApplicationStrategy;
use League\Route\Cache\Router;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;

class RouterFactory
{
    protected Router $cached_router;
    protected ServerRequestInterface $server_request;

    public function __construct(
        ServerRequestInterface $server_request,
        ResponseFactoryInterface $response_factory,
        CacheInterface $cache
    ) {
        $this->server_request = $server_request;
        $this->cached_router = new Router(
            function (\League\Route\Router $router) use ($response_factory) {
                $strategy = (new ApplicationStrategy($response_factory));
                $router->setStrategy($strategy);
                $this->collectRoutes($router);
                return $router;
            },
            $cache
        );
    }

    public function collectRoutes(\League\Route\Router $router): void
    {
        $modules = di(Modules::class)->getInstalled();
        foreach ($modules as $module) {
            $router_config = MODULES_PATH . $module . '/config/routes.php';
            if (file_exists($router_config)) {
                (require $router_config)($router);
            }
        }
    }

    /** @noinspection PhpRedundantCatchClauseInspection */
    public function dispatch(): ResponseInterface
    {
        try {
            return $this->cached_router->dispatch($this->server_request);
        } catch (NotFoundException $exception) {
            pageNotFound();
        }
    }
}
