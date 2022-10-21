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

use League\Route\Cache\Router;
use League\Route\Router as MainRouter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CachedRouter extends Router
{
    protected ?MainRouter $router = null;

    public function getRouter(): ?MainRouter
    {
        return $this->router;
    }

    public function buildRouter(ServerRequestInterface $request): MainRouter
    {
        $this->router = parent::buildRouter($request);
        return $this->router;
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return $this->router->dispatch($request);
    }
}
