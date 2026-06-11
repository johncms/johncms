<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Notifications\Application\UseCases\GetNotificationListUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class IndexController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetNotificationListUseCase $getNotificationListUseCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('notifications');
    }

    public function __invoke(): string
    {
        $this->navChain->add(__('Notifications'), '/notifications/');

        $pagination = $this->paginationFactory->create($this->getNotificationListUseCase->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $result = $this->getNotificationListUseCase->getPage($pagination->getPerPage(), $pagination->getOffset());

        $meta = new PageMeta(__('Notifications'), $pagination->getCurrentPage());
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => __('Notifications'),
            'description' => $meta->description,
        ]);

        return $this->render->render('notifications::index', [
            'data' => [
                'notifications' => $result->systemNotifications,
                'items'         => $result->items,
                'total'         => $pagination->getTotal(),
                'pagination'    => $pagination->render(),
            ],
        ]);
    }
}
