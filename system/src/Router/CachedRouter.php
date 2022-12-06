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

use InvalidArgumentException;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Laravel\SerializableClosure\SerializableClosure;
use League\Route\Router as MainRouter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;

class CachedRouter
{
    protected ?MainRouter $router = null;

    protected const CACHE_KEY = 'league/route/cache';

    /**
     * @var callable
     */
    protected $builder;

    /**
     * @var integer
     */
    protected int $ttl;

    public function __construct(callable $builder, protected CacheInterface $cache, protected bool $cacheEnabled = true)
    {
        $this->builder = $builder;
    }

    public function getRouter(): ?MainRouter
    {
        return $this->router;
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws PhpVersionNotSupportedException
     */
    public function buildRouter(ServerRequestInterface $request): MainRouter
    {
        if (true === $this->cacheEnabled && $cache = $this->cache->get(static::CACHE_KEY)) {
            $router = unserialize($cache, ['allowed_classes' => true])->getClosure()();

            if ($router instanceof MainRouter) {
                $this->router = $router;
                return $router;
            }
        }


        $builder = $this->builder;
        $router = $builder(new MainRouter());

        if (false === $this->cacheEnabled) {
            $this->router = $router;
            return $router;
        }

        if ($router instanceof MainRouter) {
            $router->prepareRoutes($request);
            $this->cache->set(static::CACHE_KEY, serialize(new SerializableClosure(fn() => $router)));
            $this->router = $router;
            return $router;
        }

        throw new InvalidArgumentException('Invalid Router builder provided to cached router');
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return $this->router->dispatch($request);
    }
}
