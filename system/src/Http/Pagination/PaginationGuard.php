<?php

declare(strict_types=1);

namespace Johncms\Http\Pagination;

use Johncms\Http\Request;

final readonly class PaginationGuard
{
    public function __construct(
        private Request $request,
    ) {
    }

    /**
     * Returns the URL to redirect to when the requested page is not canonical:
     * an explicit page=1 or an invalid value leads to the URL without the page
     * parameter, an out-of-range page leads to the last page. Returns null when
     * the requested page is valid.
     */
    public function redirectUrl(Pagination $pagination): ?string
    {
        $paramName = $pagination->getPageParamName();
        $queryParams = $this->request->query->all();
        if (! array_key_exists($paramName, $queryParams)) {
            return null;
        }

        $raw = $queryParams[$paramName];
        $page = is_scalar($raw) ? filter_var($raw, FILTER_VALIDATE_INT) : false;

        if ($page === false || $page <= 1) {
            return $pagination->getUrl(1);
        }

        if ($page > $pagination->getTotalPages()) {
            return $pagination->getUrl($pagination->getTotalPages());
        }

        return null;
    }
}
