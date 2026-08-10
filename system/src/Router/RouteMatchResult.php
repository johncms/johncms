<?php

declare(strict_types=1);

namespace Johncms\Router;

final class RouteMatchResult
{
    public const FOUND = 'FOUND';
    public const METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    public const NOT_FOUND = 'NOT_FOUND';

    /**
     * @param array<int, string> $allowedMethods
     * @param array<string, mixed> $params
     * @param list<mixed> $middlewares
     */
    public function __construct(
        public readonly string $status,
        public readonly mixed $handler = null,
        public readonly array $params = [],
        public readonly array $allowedMethods = [],
        public readonly array $middlewares = [],
        /** The module owning the matched route; null for the routes of the core. */
        public readonly ?string $module = null,
        /** Whether the matched route opted out of the CSRF check via Route::withoutCsrf(). */
        public readonly bool $csrfExempt = false,
    ) {
    }
}
