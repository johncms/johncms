<?php

declare(strict_types=1);

namespace Johncms\Router;

use Symfony\Component\Routing\Route as SymfonyRoute;

final class Route
{
    public const MODULE_ATTRIBUTE = '_module';
    public const CSRF_EXEMPT_ATTRIBUTE = '_csrf_exempt';
    public const PERMISSION_ATTRIBUTE = '_permission';
    public const PERMISSION_HIDDEN_ATTRIBUTE = '_permission_hidden';

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
     * The module this route belongs to, as its key: `johncms/forum`. Travels to the request as the
     * _module attribute, where ModuleContext turns it into the translation domain of the page —
     * the name alone, without the vendor.
     */
    public function module(string $module): self
    {
        $this->defaults[Route::MODULE_ATTRIBUTE] = $module;
        return $this;
    }

    /**
     * Exempts the route from the CSRF check of the global pipeline. Kept next to the route
     * declaration on purpose: an exemption is a security decision, and this is the one place
     * where it can be read without knowing which middleware is in play.
     */
    public function withoutCsrf(): self
    {
        $this->defaults[Route::CSRF_EXEMPT_ATTRIBUTE] = true;
        return $this;
    }

    /**
     * The permission a visitor needs to reach this route. Read by RequirePermissionMiddleware,
     * which the kernel puts in front of the route whenever the attribute is there.
     *
     * It is an attribute rather than a middleware of its own because middlewares are resolved by
     * class name and are therefore singletons: they cannot take "which permission" in their
     * constructor, and one middleware class per permission is not a design.
     *
     * @param bool $hidden Answer 404 instead of 403, for the rare route whose very existence
     *                     should not be confirmed. The default is 403: the addresses of the admin
     *                     panel are known anyway, and a refusal that says so is one a site owner
     *                     can debug.
     */
    public function permission(string $permission, bool $hidden = false): self
    {
        $this->defaults[Route::PERMISSION_ATTRIBUTE] = $permission;

        if ($hidden) {
            $this->defaults[Route::PERMISSION_HIDDEN_ATTRIBUTE] = true;
        }

        return $this;
    }

    /**
     * The permission of the surrounding group, applied only where the route has none of its own.
     */
    public function inheritPermission(string $permission, bool $hidden): self
    {
        if (isset($this->defaults[Route::PERMISSION_ATTRIBUTE])) {
            return $this;
        }

        return $this->permission($permission, $hidden);
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
