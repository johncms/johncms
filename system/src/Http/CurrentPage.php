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

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Facts about the page being served, for code that outlives a single request — templates above
 * all. It exposes the facts themselves rather than the request, so a template never has to know
 * how the current request is reached.
 */
final readonly class CurrentPage
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * Whether the homepage is being served. The route handler marks it with an attribute; the
     * path is the fallback for the requests that never reach a controller, such as an error page.
     */
    public function isHomePage(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return false;
        }

        if ($request->attributes->getBoolean('is_homepage')) {
            return true;
        }

        return $request->getPathInfo() === '/';
    }
}
