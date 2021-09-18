<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Language\Controllers;

use Johncms\Controller\BaseController;
use Throwable;

class LanguageController extends BaseController
{
    protected string $moduleName = 'johncms/language';

    /**
     * @throws Throwable
     */
    public function index(): string
    {
        return $this->render->render('language::index');
    }
}
