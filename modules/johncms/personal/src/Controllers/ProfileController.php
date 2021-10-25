<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Personal\Controllers;

use Johncms\Controller\BaseController;
use Throwable;

class ProfileController extends BaseController
{
    protected string $moduleName = 'johncms/personal';

    public function __construct()
    {
        parent::__construct();
        $this->metaTagManager->setAll(__('User Profile'));
        $this->navChain->add(__('Personal account'), route('personal.index'));
        $this->navChain->add(__('User Profile'), route('personal.profile'));
    }

    /**
     * @throws Throwable
     */
    public function index(): string
    {
        return $this->render->render('personal::profile/index', ['data' => []]);
    }
}
