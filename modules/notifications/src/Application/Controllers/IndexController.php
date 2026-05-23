<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Notifications\Application\UseCases\GetNotificationListUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class IndexController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private GetNotificationListUseCase $getNotificationListUseCase,
    ) {
        $this->controllerContext->initModule('notifications');
    }

    public function __invoke(): string
    {
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $perPage = $this->currentUser->config->kmess;

        $result = $this->getNotificationListUseCase->execute($page, $perPage);

        $this->navChain->add(__('Notifications'), '/notifications/');

        $this->render->addData([
            'title'      => __('Notifications'),
            'page_title' => __('Notifications'),
        ]);

        return $this->render->render('notifications::index', [
            'data' => [
                'notifications' => $result->systemNotifications,
                'items'         => $result->items,
                'total'         => $result->total,
                'pagination'    => $result->pagination,
            ],
        ]);
    }
}
