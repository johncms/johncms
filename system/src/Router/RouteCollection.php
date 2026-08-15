<?php

declare(strict_types=1);

namespace Johncms\Router;

final class RouteCollection
{
    /** @var list<Route> */
    private array $routeCollection = [];

    private ?string $prefix = null;
    private ?string $namePrefix = null;

    /**
     * The module the routes declared from here belong to. Stamped on every route so the request
     * pipeline can set up the module context — its translation domain — without the controller
     * naming its own module. Null for the routes of the core, which own no module.
     */
    private ?string $module = null;

    /** @var list<mixed> */
    private array $middlewares = [];

    /** Whether every route declared from here is exempt from the CSRF check. */
    private bool $csrfExempt = false;

    /** The permission every route declared from here needs, unless it names one of its own. */
    private ?string $permission = null;

    private bool $permissionHidden = false;

    /** @var list<RouteCollection> */
    private array $groups = [];

    private int $autoRouteIndex = 0;

    public function __construct(
        private readonly ?RouteRequirements $routeRequirements = null
    ) {
    }

    public function map(string|array $method, string $path, mixed $handler): Route
    {
        $route = new Route($method, $path, $handler, $this->routeRequirements);

        if ($this->module !== null) {
            $route->module($this->module);
        }

        $this->routeCollection[] = $route;
        return $route;
    }

    public function setModule(?string $module): self
    {
        $this->module = $module;
        return $this;
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

    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function setNamePrefix(string $namePrefix): self
    {
        $this->namePrefix = $namePrefix;
        return $this;
    }

    public function addMiddleware(mixed $middleware): self
    {
        array_unshift($this->middlewares, $middleware);
        return $this;
    }

    /**
     * Exempts every route of this collection, and of the groups declared inside it, from the
     * CSRF check of the global pipeline.
     */
    public function withoutCsrf(): self
    {
        $this->csrfExempt = true;
        return $this;
    }

    /**
     * The permission every route of this collection needs. A route naming its own keeps it.
     *
     * Like the middlewares of a group, it does not reach into a nested group: a section with a
     * stricter gate declares that gate itself, where it can be read.
     */
    public function permission(string $permission, bool $hidden = false): self
    {
        $this->permission = $permission;
        $this->permissionHidden = $hidden;

        return $this;
    }

    public function group(string $prefix, callable $group): RouteCollection
    {
        $collection = new self($this->routeRequirements);
        $collection->setModule($this->module);

        if ($this->csrfExempt) {
            $collection->withoutCsrf();
        }

        $group($collection);
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

            if ($this->csrfExempt) {
                $route->withoutCsrf();
            }

            if ($this->permission !== null) {
                $route->inheritPermission($this->permission, $this->permissionHidden);
            }

            $name = $route->getName() ?? 'legacy_route_' . ++$this->autoRouteIndex;
            $routes->add($name, $route->compile(), $route->getPriority());
        }

        foreach ($this->groups as $group) {
            $routes->addCollection($group->compile());
        }

        if ($this->prefix !== null) {
            $routes->addPrefix($this->prefix);
        }

        if ($this->namePrefix !== null) {
            $routes->addNamePrefix($this->namePrefix);
        }

        return $routes;
    }
}
