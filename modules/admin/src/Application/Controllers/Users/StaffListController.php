<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\UseCases\GetStaffListUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

final readonly class StaffListController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private NavChain $navChain,
        private GetStaffListUseCase $getStaffListUseCase,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function __invoke(): ViewResponse
    {
        $staffList = $this->getStaffListUseCase->execute();

        $title = __('Administration');
        $this->navChain->add($title);

        return new ViewResponse(
            '@admin/staff.twig',
            [
                'title'      => $title,
                'page_title' => $title,
                'usr_menu'   => ['adminlist' => true],
                'groups'     => $staffList->groups,
                'total'      => $staffList->total,
            ]
        );
    }
}
