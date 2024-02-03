<?php

declare(strict_types=1);

namespace Johncms\Router;

final class Route
{
    private array | string $method;
    private string $path;
    private mixed $handler;
    private ?string $name = null;
    private array $middlewares = [];
    private int $priority = 0;

    public function __construct(array | string $method, string $path, mixed $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
    }

    public function setName(string $name): Route
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name ?? $this->path;
    }

    public function setPriority(int $priority): Route
    {
        $this->priority = $priority;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function addMiddleware(mixed $middleware): Route
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function prependMiddleware(mixed $middleware): Route
    {
        array_unshift($this->middlewares, $middleware);
        return $this;
    }

    public function setMiddlewares(array $middlewares): Route
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    public function compile(): \Symfony\Component\Routing\Route
    {
        $compiledRoute = new \Symfony\Component\Routing\Route($this->path, [
            '_controller'  => $this->handler,
            '_middlewares' => $this->middlewares,
            '_name'        => $this->name,
        ]);
        $compiledRoute->setMethods($this->method);

        return $compiledRoute;
    }
}
