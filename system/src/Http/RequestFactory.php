<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Http;

use Psr\Container\ContainerInterface;

/**
 * Builds the request for the classic FPM runtime.
 *
 * It registers our Request subclass as HttpFoundation's factory so createFromGlobals()
 * returns a Johncms\Http\Request, applies the trusted-proxy configuration, and returns the
 * populated request. Under a worker runtime (stage 6) the kernel supplies the request instead
 * and this factory is only used for the classic per-request FPM mode.
 */
final readonly class RequestFactory
{
    private const DEFAULT_TRUSTED_HEADERS = Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PROTO
        | Request::HEADER_X_FORWARDED_PORT;

    public function __invoke(ContainerInterface $container): Request
    {
        self::configure();

        return Request::createFromGlobals();
    }

    /**
     * Register the Request subclass factory, trusted proxies and trusted hosts. Idempotent and
     * safe to call once per process (including once before a worker loop).
     */
    public static function configure(): void
    {
        Request::setFactory(static fn (...$args): Request => new Request(...$args));

        $trustedProxies = config('http.trusted_proxies', []);
        if (is_array($trustedProxies) && $trustedProxies !== []) {
            $trustedHeaders = config('http.trusted_headers');
            Request::setTrustedProxies(
                $trustedProxies,
                is_int($trustedHeaders) ? $trustedHeaders : self::DEFAULT_TRUSTED_HEADERS,
            );
        }

        self::configureTrustedHosts();
    }

    /**
     * Restrict the host the application is willing to serve. Without it the Host and (behind a
     * trusted proxy) X-Forwarded-Host headers are taken at face value, so a client can control
     * the host used in generated absolute URLs — password-reset links, cache keys, redirects.
     *
     * An empty list keeps the permissive default, which is correct for a single-site install
     * whose web server already rejects unknown hosts. A non-matching host makes HttpFoundation
     * throw SuspiciousOperationException, which the kernel maps to a 400 (plan stage 3).
     */
    private static function configureTrustedHosts(): void
    {
        $trustedHosts = config('http.trusted_hosts', []);
        if (! is_array($trustedHosts)) {
            // Guard against an operator typo (a bare string instead of a list): a scalar here
            // would otherwise become a TypeError at boot.
            return;
        }

        $patterns = array_values(array_filter($trustedHosts, 'is_string'));
        if ($patterns === []) {
            return;
        }

        Request::setTrustedHosts($patterns);
    }
}
