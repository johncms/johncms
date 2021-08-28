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

use Johncms\i18n\Translator;
use Johncms\NavChain;
use Johncms\View\MetaTagManager;
use Johncms\View\Render;

class BaseController extends AbstractController
{
    protected Render $render;
    protected Translator $translator;
    protected NavChain $nav_chain;
    protected MetaTagManager $metaTagManager;

    /** @var string The module name */
    protected string $module_name = '';

    public function __construct()
    {
        $this->render = di(Render::class);
        $this->translator = di(Translator::class);
        $this->nav_chain = di(NavChain::class);
        $this->metaTagManager = di(MetaTagManager::class);

        if (! empty($this->module_name)) {
            // Register Namespace for module templates
            $this->render->addFolder($this->module_name, MODULES_PATH . $this->module_name . '/templates/');

            // Register the module languages domain and folder
            $this->translator->addTranslationDomain($this->module_name, MODULES_PATH . $this->module_name . '/locale');
        }
    }
}
