<?php

declare(strict_types=1);

namespace Johncms\Security;

use RuntimeException;

/**
 * The path exemptions of config/csrf.php.
 *
 * The primary way to exempt an endpoint from the CSRF check is Route::withoutCsrf(), which keeps
 * the decision next to the route. This list covers what that cannot reach: entry points whose
 * routes belong to a third-party module, and an emergency hatch that needs no code deploy.
 */
final readonly class CsrfExemptions
{
    /**
     * @param list<string> $paths Normalized paths, shell wildcards allowed.
     */
    public function __construct(private array $paths = [])
    {
    }

    public static function create(): self
    {
        $config = require CONFIG_PATH . 'csrf.php';

        if (! is_array($config)) {
            throw new RuntimeException('config/csrf.php must return an array.');
        }

        $paths = $config['except'] ?? [];

        if (! is_array($paths)) {
            throw new RuntimeException('The "except" key of config/csrf.php must be an array of paths.');
        }

        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                throw new RuntimeException('Every entry of "except" in config/csrf.php must be a non-empty string.');
            }
        }

        return new self(array_values($paths));
    }

    public function exempts(string $path): bool
    {
        foreach ($this->paths as $pattern) {
            if ($pattern === $path || fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The configured patterns, so the test guarding the exemption list can assert on them.
     *
     * @return list<string>
     */
    public function all(): array
    {
        return $this->paths;
    }
}
