<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Notifications\Application\UseCases\GetNotificationListUseCase;
use Johncms\NavChain;

final readonly class IndexController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private GetNotificationListUseCase $getNotificationListUseCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('notifications');
    }

    public function __invoke(): ViewResponse
    {
        $this->navChain->add(__('Notifications'), '/notifications/');

        $pagination = $this->paginationFactory->create($this->getNotificationListUseCase->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $result = $this->getNotificationListUseCase->getPage($pagination->getPerPage(), $pagination->getOffset());

        $meta = new PageMeta(__('Notifications'), $pagination->getCurrentPage());

        return new ViewResponse(
            '@notifications/public/index.twig',
            [
                'title'         => $meta->title,
                'page_title'    => __('Notifications'),
                'description'   => $meta->description,
                'notifications' => $result->systemNotifications,
                'items'         => $result->items,
                'total'         => $pagination->getTotal(),
                'pagination'    => $pagination->hasPages() ? $pagination->render() : null,
            ]
        );
    }
}
