<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Request;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Online\Application\FiltersBuilder;
use Johncms\Modules\Online\Application\UseCases\GetIpActivityUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

final readonly class IpController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private FiltersBuilder $filtersBuilder,
        private GetIpActivityUseCase $ipActivity,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('online');
    }

    public function __invoke(Request $request): ViewResponse
    {
        $pageTitle = __('IP Activity');

        $this->navChain->add(__('Online'), '/online/');

        $filters = $this->filtersBuilder->build('ip');

        $pagination = $this->paginationFactory->create($this->ipActivity->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $meta = new PageMeta($pageTitle . ' — ' . __('Online'), $pagination->getCurrentPage());
        $total = $pagination->getTotal();
        $items = $total > 0
            ? $this->ipActivity->getPage(
                $pagination->getPerPage(),
                $pagination->getOffset(),
                $request->getClientIp() ?? ''
            )
            : [];

        return new ViewResponse(
            '@online/public/ip.twig',
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
