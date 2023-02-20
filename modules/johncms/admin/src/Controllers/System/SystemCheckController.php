<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Admin\Controllers\System;

use Johncms\Checker\SystemChecker;
use Johncms\Controller\BaseAdminController;

class SystemCheckController extends BaseAdminController
{
    protected string $moduleName = 'johncms/admin';

    public function index(SystemChecker $checker): string
    {
        $this->metaTagManager->setAll(__('System check'));
        $this->navChain->add(__('System check'));

        return $this->render->render(
            'johncms/admin::system/system_check',
            [
                'data' => [
                    'required_checks' => $checker->checkExtensions(),
                    'recommendations' => $checker->recommendations(),
                    'database'        => $checker->checkDatabase(),
                ],
            ]
        );
    }
}
