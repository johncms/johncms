<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Controller;

use Johncms\NavChain;
use Johncms\System\i18n\Translator;
use Johncms\System\View\Render;

class BaseController extends AbstractController
{
    /** @var Render */
    protected $render;

    /** @var Translator */
    protected $translator;

    /** @var NavChain */
    protected $nav_chain;

    /** @var string The module name */
    protected $module_name = '';

    public function __construct()
    {
        $this->render = di(Render::class);
        $this->translator = di(Translator::class);
        $this->nav_chain = di(NavChain::class);

        if (! empty($this->module_name)) {
            // Register Namespace for module templates
            $this->render->addFolder($this->module_name, MODULES_PATH . $this->module_name . '/templates/');

            // Register the module languages domain and folder
            $this->translator->addTranslationDomain($this->module_name, MODULES_PATH . $this->module_name . '/locale');
        }
    }
}
