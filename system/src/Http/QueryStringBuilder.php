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
 * Builds a path + query string from a set of query parameters, with parameters to remove
 * and to add. Extracted from the former Request::getQueryString() (stage 1a): the name
 * collides with HttpFoundation's own Request::getQueryString(), and query-string assembly is
 * a presentation concern rather than request state. Request-agnostic on purpose so it survives
 * the base-class switch in stage 1d.
 */
final readonly class QueryStringBuilder
{
    /**
     * @param array<string, mixed> $queryParams Current query parameters (e.g. $_GET).
     * @param list<string>         $removeParams Parameter names to drop from the result.
     * @param array<string, mixed> $addParams Parameters to add or override.
     */
    public function build(string $path, array $queryParams, array $removeParams = [], array $addParams = []): string
    {
        if ($removeParams !== []) {
            $queryParams = array_diff_key($queryParams, array_flip($removeParams));
        }

        $queryParams = array_merge($queryParams, $addParams);
        $queryString = http_build_query($queryParams);

        return $path . ($queryString !== '' ? '?' . $queryString : '');
    }
}
