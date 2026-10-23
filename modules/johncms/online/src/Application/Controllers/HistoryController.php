<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\Controllers;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Online\Application\FiltersBuilder;
use Johncms\Modules\Online\Application\UseCases\GetUsersHistoryUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

final readonly class HistoryController
{
    public function __construct(
        private NavChain $navChain,
        private FiltersBuilder $filtersBuilder,
        private GetUsersHistoryUseCase $usersHistory,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $pageTitle = __('History');

        $this->navChain->add(__('Online'), '/online/');

        $filters = $this->filtersBuilder->build('history');

        $pagination = $this->paginationFactory->create($this->usersHistory->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $meta = new PageMeta($pageTitle . ' — ' . __('Online'), $pagination->getCurrentPage());
        $total = $pagination->getTotal();
        $items = $total > 0
            ? $this->usersHistory->getPage($pagination->getPerPage(), $pagination->getOffset())
            : [];

        return new ViewResponse(
            '@online/public/users.twig',
            [
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
                'filters'     => $filters,
                'pagination'  => $pagination->render(),
                'total'       => $total,
                'items'       => $items,
            ]
        );
    }
}
