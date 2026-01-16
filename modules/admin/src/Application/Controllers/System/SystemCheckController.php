<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\System;

use Johncms\Checker\SystemChecker;
use Johncms\Http\Controller\AdminControllerContext;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class SystemCheckController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(SystemChecker $checker): string
    {
        $this->render->addData(
            [
                'title' => __('System check'),
                'page_title' => __('System check'),
                'sys_menu' => ['system_check' => true],
            ]
        );
        $this->navChain->add(__('System check'));

        $check_extensions = $checker->checkExtensions();
        $recommendations = $checker->recommendations();
        $database = $checker->checkDatabase();

        return $this->render->render(
            'admin::system/system_check',
            [
                'data' => [
                    'required_checks' => $check_extensions,
                    'recommendations' => $recommendations,
                    'database'        => $database,
                ],
            ]
        );
    }
}
