<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Help\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class ForumRulesController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
    ) {
        $this->controllerContext->initModule('help');
    }

    public function __invoke(): string
    {
        $title = __('Forum rules');

        $this->navChain->add(__('Information, FAQ'), '/help/');
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render('help::forum_rules', [
            'back_url' => '/help/',
        ]);
    }
}
