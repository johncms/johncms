<?php

declare(strict_types=1);

namespace Johncms\Http\Pagination;

use Johncms\Http\QueryStringBuilder;
use Johncms\Http\Request;
use Johncms\View\RendererInterface;
use Johncms\Auth\CurrentUser;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The request comes from the RequestStack rather than being held directly: this is a shared
 * service, and a request captured at construction would be the wrong one for every request but
 * the first under a long-running runtime.
 */
final readonly class PaginationFactory
{
    public function __construct(
        private RequestStack $requestStack,
        private RendererInterface $renderer,
        private CurrentUser $currentUser,
        private QueryStringBuilder $queryStringBuilder,
    ) {
    }

    public function create(
        int $total,
        ?int $perPage = null,
        string $pageParamName = 'page',
        ?int $currentPage = null,
    ): Pagination {
        $request = $this->request();
        $perPage ??= (int) $this->currentUser->user()->config->kmess;
        $currentPage ??= $request->queryInt($pageParamName, 1);

        return new Pagination(
            queryStringBuilder: $this->queryStringBuilder,
            renderer:           $this->renderer,
            currentPath:        $request->getPathInfo(),
            currentQuery:       $request->query->all(),
            total:              $total,
            perPage:            $perPage,
            currentPage:        $currentPage,
            pageParamName:      $pageParamName,
        );
    }

    private function request(): Request
    {
        $request = $this->requestStack->getCurrentRequest();

        if (! $request instanceof Request) {
            throw new RuntimeException('No request is being served: the request stack is empty.');
        }

        return $request;
    }
}
