<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Router;

use Johncms\Router\Strategy\ApplicationStrategy;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Router;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Throwable;

class RouterFactory
{
    protected CachedRouter $cachedRouter;
    protected ServerRequestInterface $serverRequest;

    public function __construct(
        ServerRequestInterface $serverRequest,
        ResponseFactoryInterface $responseFactory,
        CacheInterface $cache
    ) {
        $this->serverRequest = $serverRequest;
        $this->cachedRouter = new CachedRouter(
            function (Router $router) use ($responseFactory) {
                $strategy = (new ApplicationStrategy($responseFactory));
                $router->setStrategy($strategy);

                // Set global middleware
                $config = di('config');
                $router->lazyMiddlewares($config['middleware'] ?? []);

                $this->collectRoutes($router);
                return $router;
            },
            $cache
        );
    }

    public function collectRoutes(Router $router): void
    {
        $routerConfigs = glob(MODULES_PATH . '*/*/config/routes.php');
        foreach ($routerConfigs as $routerConfig) {
            (require $routerConfig)($router);
        }
    }

    /**
     * @noinspection PhpRedundantCatchClauseInspection
     * @throws Throwable
     */
    public function dispatch(): ResponseInterface
    {
        try {
            return $this->cachedRouter->dispatch($this->serverRequest);
        } catch (NotFoundException) {
            return status_page(404);
        }
    }

    public function getRouter(): Router
    {
        $router = $this->cachedRouter->getRouter();
        if ($router === null) {
            throw new RuntimeException('The router is not configured yet');
        }
        return $router;
    }
}
