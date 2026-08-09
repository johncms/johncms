<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\UseCases\GetDashboardContextUseCase;

final readonly class DashboardController
{
    public function __construct(
        private GetDashboardContextUseCase $getDashboardContextUseCase,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        return new ViewResponse(
            '@admin/index.twig',
            [
                'title'      => __('Admin Panel'),
                'page_title' => __('Dashboard'),
                'data'       => $this->getDashboardContextUseCase->execute(),
            ]
        );
    }
}
