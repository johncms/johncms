<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Admin\Controllers\System;

use Admin\Controllers\BaseAdminController;
use Johncms\Checker\SystemChecker;

class SystemCheckController extends BaseAdminController
{
    protected $module_name = 'admin';

    public function index(SystemChecker $checker): string
    {
        $this->render->addData(
            [
                'title'      => __('System check'),
                'page_title' => __('System check'),
                'sys_menu'   => ['system_check' => true],
            ]
        );
        $this->nav_chain->add(__('System check'));

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
