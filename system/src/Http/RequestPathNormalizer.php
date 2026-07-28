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

/**
 * Turns the raw path of a request into the path the route collection is matched against.
 *
 * Extracted from public/index.php, where it was an anonymous function reading
 * $_SERVER directly. The routes are declared without a trailing slash and with decoded
 * characters, so both normalizations have to happen before matching.
 */
final readonly class RequestPathNormalizer
{
    /** Entry point of JohnCMS 8 and older; kept working so old links and search results survive. */
    private const LEGACY_FORUM_ENTRY_POINT = '/forum/index.php';

    public function normalize(Request $request): string
    {
        // The full request URI, not getPathInfo(): the latter subtracts the front-controller
        // prefix, which would make /index.php/<any route> a second, working alias of every route
        // (and, through RequestContext::fromRequest(), stick that prefix into generated links).
        $path = $request->getRequestUri();
        if (false !== $queryPosition = strpos($path, '?')) {
            $path = substr($path, 0, $queryPosition);
        }

        // The routes are declared decoded, so the path has to be decoded before matching.
        $path = rawurldecode($path);

        if ($path !== '/') {
            $path = rtrim($path, '/');
            if ($path === '') {
                $path = '/';
            }
        }

        if ($path === self::LEGACY_FORUM_ENTRY_POINT) {
            return '/forum';
        }

        return $path;
    }
}
