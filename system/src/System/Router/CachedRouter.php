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

use League\Route\Cache\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CachedRouter extends Router
{
    protected ?\League\Route\Router $router = null;

    public function getRouter(): ?\League\Route\Router
    {
        return $this->router;
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $this->router = $this->buildRouter($request);
        return $this->router->dispatch($request);
    }
}
