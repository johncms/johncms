<?php

declare(strict_types=1);

namespace Johncms\Router;

final class RouteCollection
{
    /** @var list<Route> */
    private array $routeCollection = [];
    private ?string $prefix = null;
    private ?string $namePrefix = null;

    /** @var list<mixed> */
    private array $middlewares = [];

    /** @var list<RouteCollection> */
    private array $groups = [];

    public function __construct(
        private readonly ?RouteRequirements $routeRequirements = null
    ) {
    }

    public function map(string | array $method, string $path, mixed $handler): Route
    {
        $route = new Route($method, $path, $handler, $this->routeRequirements);
        $this->routeCollection[] = $route;
        return $route;
    }

    public function get(string $path, mixed $handler): Route
    {
        return $this->map('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): Route
    {
        return $this->map('POST', $path, $handler);
    }

    public function delete(string $path, mixed $handler): Route
    {
        return $this->map('DELETE', $path, $handler);
    }

    public function head(string $path, mixed $handler): Route
    {
        return $this->map('HEAD', $path, $handler);
    }

    public function options(string $path, mixed $handler): Route
    {
        return $this->map('OPTIONS', $path, $handler);
    }

    public function patch(string $path, mixed $handler): Route
    {
        return $this->map('PATCH', $path, $handler);
    }

    public function put(string $path, mixed $handler): Route
    {
        return $this->map('PUT', $path, $handler);
    }

    public function setPrefix(string $prefix): RouteCollection
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function setNamePrefix(string $namePrefix): RouteCollection
    {
        $this->namePrefix = $namePrefix;
        return $this;
    }

    public function addMiddleware(mixed $middleware): RouteCollection
    {
        array_unshift($this->middlewares, $middleware);
        return $this;
    }

    public function group(string $prefix, callable $group): RouteCollection
    {
        $collection = new RouteCollection($this->routeRequirements);
        ($group)($collection);
        $collection->setPrefix($prefix);
        $this->groups[] = $collection;
        return $collection;
    }

    public function compile(): \Symfony\Component\Routing\RouteCollection
    {
        $routes = new \Symfony\Component\Routing\RouteCollection();
        foreach ($this->routeCollection as $route) {
            foreach ($this->middlewares as $middleware) {
                $route->prependMiddleware($middleware);
            }
            $routes->add($route->getName(), $route->compile(), $route->getPriority());
        }

        // Add groups to the main collection
        foreach ($this->groups as $group) {
            $routes->addCollection($group->compile());
        }

        if ($this->prefix) {
            $routes->addPrefix($this->prefix);
        }

        if ($this->namePrefix) {
            $routes->addNamePrefix($this->namePrefix);
        }

        return $routes;
    }
}
