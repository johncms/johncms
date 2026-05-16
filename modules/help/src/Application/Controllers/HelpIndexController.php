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
use Johncms\Modules\Help\Application\HelpLegacyRedirectHandler;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class HelpIndexController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private HelpLegacyRedirectHandler $legacyRedirectHandler,
    ) {
        $this->controllerContext->initModule('help');
    }

    public function __invoke(): string
    {
        $this->legacyRedirectHandler->handle();

        $title = __('Information, FAQ');

        $this->navChain->add($title, '/help/');

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render('help::index', [
            'back_url' => '/',
        ]);
    }
}
