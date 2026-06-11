<?php

declare(strict_types=1);

namespace Johncms\Http\Pagination;

use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class PaginationFactory
{
    public function __construct(
        private Request $request,
        private Render $render,
        private User $user,
    ) {
    }

    public function create(
        int $total,
        ?int $perPage = null,
        string $pageParamName = 'page',
        ?int $currentPage = null,
    ): Pagination {
        $perPage ??= (int) $this->user->config->kmess;
        $currentPage ??= (int) $this->request->getQuery($pageParamName, 1, FILTER_VALIDATE_INT);

        return new Pagination(
            request:       $this->request,
            renderer:      $this->render,
            total:         $total,
            perPage:       $perPage,
            currentPage:   $currentPage,
            pageParamName: $pageParamName,
        );
    }
}
