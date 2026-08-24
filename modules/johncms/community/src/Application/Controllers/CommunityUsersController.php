<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\Controllers;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Community\Application\UseCases\ViewUsersUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

final readonly class CommunityUsersController
{
    public function __construct(
        private NavChain $navChain,
        private ViewUsersUseCase $viewUsersUseCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $communityTitle = __('Community');
        $this->navChain->add($communityTitle, '/community/');


        $pageTitle = __('List of users');
        $this->navChain->add($pageTitle);

        $pagination = $this->paginationFactory->create($this->viewUsersUseCase->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $total = $pagination->getTotal();
        $list = $total > 0
            ? $this->viewUsersUseCase->getPage($pagination->getPerPage(), $pagination->getOffset())
            : [];

        $meta = new PageMeta($pageTitle, $pagination->getCurrentPage());

        return new ViewResponse(
            '@community/public/users.twig',
            [
                'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
                'total'       => $total,
                'list'        => $list,
            ]
        );
    }
}
