<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Router;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Throwable;

/**
 * Builds the thing that turns a path into a route, from the cache when there is one.
 *
 * With CACHE_ROUTES on, the routes are dumped once as a plain nested array — no objects, so
 * opcache keeps it — and the collection itself is never built again: none of the twenty-odd
 * routes.php files are read, and no Route object exists. Which is only possible because the
 * collection stopped depending on who is asking: a route closed to the visitor is declared all
 * the same and its gate is a middleware.
 *
 * Delete data/cache/routes.php, or run cache:clear, after changing a route.
 */
final readonly class UrlMatcherFactory
{
    public const CACHE_FILE = 'routes.php';

    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param callable(): RouteCollection $routes Builds the collection; never called when the
     *                                            dump is already there.
     */
    public function create(callable $routes, RequestContext $context, bool $useCache): UrlMatcherInterface
    {
        if (! $useCache) {
            return new UrlMatcher($routes(), $context);
        }

        $cacheFile = CACHE_PATH . self::CACHE_FILE;

        if (is_file($cacheFile)) {
            /** @var array<int, mixed> $compiled */
            $compiled = require $cacheFile;

            return new CompiledUrlMatcher($compiled, $context);
        }

        $compiled = (new CompiledUrlMatcherDumper($routes()))->getCompiledRoutes();

        $this->write($cacheFile, $compiled);

        return new CompiledUrlMatcher($compiled, $context);
    }

    /**
     * A route whose handler or middleware is a closure cannot be dumped. The site is served
     * either way — it just goes on building the collection every request, and says why once.
     *
     * @param array<int, mixed> $compiled
     */
    private function write(string $cacheFile, array $compiled): void
    {
        try {
            $php = '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($compiled, true) . ';' . PHP_EOL;
        } catch (Throwable $exception) {
            $this->logger->warning(
                'The routes could not be cached; something in them cannot be written out as data.',
                ['exception' => $exception]
            );

            return;
        }

        // Written next to the target and moved into place: a request arriving mid-write would
        // otherwise require half a file.
        $temporary = $cacheFile . '.' . getmypid();

        if (file_put_contents($temporary, $php) === false || ! rename($temporary, $cacheFile)) {
            @unlink($temporary);
            $this->logger->warning('The routes could not be cached: ' . $cacheFile . ' is not writable.');
        }
    }
}
