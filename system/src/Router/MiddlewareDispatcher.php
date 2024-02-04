<?php

declare(strict_types=1);

namespace Johncms\Router;

use Illuminate\Container\Container;
use InvalidArgumentException;
use OutOfBoundsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareDispatcher implements RequestHandlerInterface
{
    private array $middlewares = [];

    public function __construct(
        private readonly Container $container
    ) {
        $this->addGlobalMiddlewares();
    }

    private function addGlobalMiddlewares(): void
    {
        $globalMiddlewares = config('middleware');
        foreach ($globalMiddlewares as $globalMiddleware) {
            $this->addMiddleware($globalMiddleware);
        }
    }

    public function addMiddleware(mixed $middleware): void
    {
        $this->middlewares[] = $this->resolveMiddleware($middleware);
    }

    private function resolveMiddleware($middleware): MiddlewareInterface
    {
        if (is_string($middleware)) {
            $middleware = $this->container->get($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        throw new InvalidArgumentException(sprintf('Could not resolve middleware class: %s', $middleware));
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->shiftMiddleware();
        return $middleware->process($request, $this);
    }

    public function shiftMiddleware(): MiddlewareInterface
    {
        $middleware = array_shift($this->middlewares);

        if ($middleware === null) {
            throw new OutOfBoundsException('Reached end of middleware stack. Does your controller return a response?');
        }

        return $middleware;
    }
}
