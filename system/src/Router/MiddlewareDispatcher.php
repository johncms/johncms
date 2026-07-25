<?php

declare(strict_types=1);

namespace Johncms\Router;

use InvalidArgumentException;
use Johncms\Http\Request;
use Psr\Container\ContainerInterface;

final class MiddlewareDispatcher
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    /**
     * @param list<mixed> $middlewares
     */
    public function dispatch(Request $request, array $middlewares, callable $handler): mixed
    {
        $pipeline = $this->buildPipeline($middlewares, $handler);

        return $pipeline($request);
    }

    /**
     * @param list<mixed> $middlewares
     */
    private function buildPipeline(array $middlewares, callable $handler): callable
    {
        $next = fn (Request $request): mixed => $handler($request);

        foreach (array_reverse($middlewares) as $middleware) {
            $next = $this->wrapMiddleware($middleware, $next);
        }

        return $next;
    }

    private function wrapMiddleware(mixed $middleware, callable $next): callable
    {
        return fn (Request $request): mixed => $this->callMiddleware($middleware, $request, $next);
    }

    private function callMiddleware(mixed $middleware, Request $request, callable $next): mixed
    {
        $resolved = $this->resolveMiddleware($middleware);

        if ($resolved instanceof MiddlewareInterface) {
            return $resolved->handle($request, $next);
        }

        return $resolved($request, $next);
    }

    private function resolveMiddleware(mixed $middleware): callable|MiddlewareInterface
    {
        if (is_string($middleware)) {
            $middleware = $this->container->get($middleware);
        }

        if ($middleware instanceof MiddlewareInterface || is_callable($middleware)) {
            return $middleware;
        }

        throw new InvalidArgumentException('Could not resolve middleware');
    }
}
