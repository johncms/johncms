<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Online\Application\FiltersBuilder;
use Johncms\Modules\Online\Application\UseCases\GetIpActivityUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class IpController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private FiltersBuilder $filtersBuilder,
        private GetIpActivityUseCase $ipActivity,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('online');
    }

    public function __invoke(): string
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
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        $total = $pagination->getTotal();
        $items = $total > 0
            ? $this->ipActivity->getPage($pagination->getPerPage(), $pagination->getOffset())
            : [];

        return $this->render->render('online::ip', [
            'data' => [
                'filters'    => $filters,
                'pagination' => $pagination->render(),
                'total'      => $total,
                'items'      => $items,
            ],
        ]);
    }
}
