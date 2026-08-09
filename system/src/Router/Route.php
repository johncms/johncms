<?php

declare(strict_types=1);

namespace Johncms\Router;

use Symfony\Component\Routing\Route as SymfonyRoute;

final class Route
{
    public const MODULE_ATTRIBUTE = '_module';

    private ?string $name = null;
    private int $priority = 0;

    /** @var array<string, mixed> */
    private array $defaults = [];

    /** @var array<string, string> */
    private array $requirements = [];

    /** @var list<mixed> */
    private array $middlewares = [];

    public function __construct(
        private readonly string|array $method,
        private readonly string $path,
        private readonly mixed $handler,
        private readonly ?RouteRequirements $routeRequirements = null,
    ) {
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setName(string $name): self
    {
        return $this->name($name);
    }

    /**
     * The module this route belongs to. Travels to the request as the _module attribute, where
     * ModuleContextMiddleware turns it into the translation domain of the page.
     */
    public function module(string $module): self
    {
        $this->defaults[Route::MODULE_ATTRIBUTE] = $module;
        return $this;
    }

    public function priority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @param array<string, mixed> $defaults
     */
    public function defaults(array $defaults): self
    {
        $this->defaults = array_merge($this->defaults, $defaults);
        return $this;
    }

    /**
     * @param array<string, string> $requirements
     */
    public function requirements(array $requirements): self
    {
        $this->requirements = array_merge($this->requirements, $requirements);
        return $this;
    }

    public function middleware(mixed $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function addMiddleware(mixed $middleware): self
    {
        return $this->middleware($middleware);
    }

    public function prependMiddleware(mixed $middleware): self
    {
        array_unshift($this->middlewares, $middleware);
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function compile(): SymfonyRoute
    {
        $defaults = array_merge(
            ['_handler' => $this->handler],
            $this->defaults
        );

        if ($this->middlewares !== []) {
            $defaults['_middlewares'] = $this->middlewares;
        }

        return new SymfonyRoute(
            path: $this->routeRequirements?->replaceTemplates($this->path) ?? $this->path,
            defaults: $defaults,
            requirements: $this->requirements,
            methods: is_array($this->method) ? $this->method : [$this->method],
        );
    }
}
