<?php

declare(strict_types=1);

namespace Johncms\Router;

use InvalidArgumentException;
use Johncms\Http\Request;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

final class MiddlewareDispatcher
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    /**
     * @param list<mixed> $middlewares
     * @param callable(Request): Response $handler
     */
    public function dispatch(Request $request, array $middlewares, callable $handler): Response
    {
        $pipeline = $this->buildPipeline($middlewares, $handler);

        return $pipeline($request);
    }

    /**
     * @param list<mixed> $middlewares
     * @param callable(Request): Response $handler
     */
    private function buildPipeline(array $middlewares, callable $handler): callable
    {
        $next = fn (Request $request): Response => $handler($request);

        foreach (array_reverse($middlewares) as $middleware) {
            $next = $this->wrapMiddleware($middleware, $next);
        }

        return $next;
    }

    /**
     * @param callable(Request): Response $next
     */
    private function wrapMiddleware(mixed $middleware, callable $next): callable
    {
        return fn (Request $request): Response => $this->callMiddleware($middleware, $request, $next);
    }

    /**
     * @param callable(Request): Response $next
     */
    private function callMiddleware(mixed $middleware, Request $request, callable $next): Response
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
