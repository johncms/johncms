<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\UseCases\GetDashboardContextUseCase;
use Johncms\System\View\Render;

final readonly class DashboardController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private GetDashboardContextUseCase $getDashboardContextUseCase,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function __invoke(): string
    {
        $context = $this->getDashboardContextUseCase->execute();

        $this->render->addData(
            [
                'title'      => __('Admin Panel'),
                'page_title' => __('Dashboard'),
            ]
        );

        return $this->render->render(
            'admin::index',
            [
                'data' => $context,
            ]
        );
    }
}
