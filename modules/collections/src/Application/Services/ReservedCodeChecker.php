<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Symfony\Component\Routing\RouteCollection;

/**
 * Rejects collection codes that would be shadowed by an existing top-level URL
 * segment. A collection owns its root segment (`/blog`), so its code must not
 * clash with a real route (`/admin`, `/news`, …) or a reserved filesystem path,
 * otherwise the collection would be unreachable behind the higher-priority route.
 */
final readonly class ReservedCodeChecker implements ReservedCodeCheckerInterface
{
    /** Top-level paths reserved by the filesystem/entry points, not backed by routes. */
    private const EXTRA_RESERVED = ['admin', 'assets', 'upload', 'system', 'data', 'install', 'vendor', 'index.php'];

    public function __construct(
        private RouteCollection $routes,
    ) {
    }

    public function isReserved(string $code): bool
    {
        return in_array($code, $this->reservedSegments(), true);
    }

    /**
     * @return list<string>
     */
    private function reservedSegments(): array
    {
        $segments = self::EXTRA_RESERVED;
        foreach ($this->routes->all() as $route) {
            $first = strtok(ltrim($route->getPath(), '/'), '/');
            // Skip placeholder-first paths (e.g. the collections catch-all `/{route}`).
            if (is_string($first) && $first !== '' && ! str_contains($first, '{')) {
                $segments[] = $first;
            }
        }

        return array_values(array_unique($segments));
    }
}
