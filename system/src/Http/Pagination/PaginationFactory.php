<?php

declare(strict_types=1);

namespace Johncms\Http\Pagination;

use Johncms\Http\QueryStringBuilder;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class PaginationFactory
{
    public function __construct(
        private Request $request,
        private Render $render,
        private User $user,
        private QueryStringBuilder $queryStringBuilder,
    ) {
    }

    public function create(
        int $total,
        ?int $perPage = null,
        string $pageParamName = 'page',
        ?int $currentPage = null,
    ): Pagination {
        $perPage ??= (int) $this->user->config->kmess;
        $currentPage ??= $this->request->queryInt($pageParamName, 1);

        return new Pagination(
            queryStringBuilder: $this->queryStringBuilder,
            renderer:           $this->render,
            currentPath:        $this->request->getPathInfo(),
            currentQuery:       $this->request->query->all(),
            total:              $total,
            perPage:            $perPage,
            currentPage:        $currentPage,
            pageParamName:      $pageParamName,
        );
    }
}
