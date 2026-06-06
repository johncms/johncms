<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\UseCases\GetStaffListUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class StaffListController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetStaffListUseCase $getStaffListUseCase,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function __invoke(): string
    {
        $staffList = $this->getStaffListUseCase->execute();

        $title = __('Administration');
        $this->navChain->add($title);

        $this->render->addData(
            [
                'title'      => $title,
                'page_title' => $title,
                'usr_menu'   => ['adminlist' => true],
            ]
        );

        return $this->render->render(
            'admin::admin_list',
            [
                'data' => $staffList,
            ]
        );
    }
}
