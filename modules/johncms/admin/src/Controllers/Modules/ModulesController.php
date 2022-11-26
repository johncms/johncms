<?php

declare(strict_types=1);

namespace Johncms\Admin\Controllers\Modules;

use Johncms\Controller\BaseAdminController;
use Johncms\Modules\Modules;

class ModulesController extends BaseAdminController
{
    protected string $moduleName = 'johncms/admin';

    public function index(): string
    {
        $this->metaTagManager->setAll(__('List of Modules'));
        return $this->render->render('admin::modules/index', [
            'data' => [
                'modules' => Modules::getModulesWithMetaData(),
            ],
        ]);
    }
}
