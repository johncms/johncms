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
use Johncms\Http\Request;
use Johncms\Modules\Help\Application\HelpLegacyRedirectHandler;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

final readonly class HelpIndexController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private HelpLegacyRedirectHandler $legacyRedirectHandler,
    ) {
        $this->controllerContext->initModule('help');
    }

    public function __invoke(Request $request): ViewResponse
    {
        $this->legacyRedirectHandler->handle($request);

        $title = __('Information, FAQ');

        $this->navChain->add($title, '/help/');

        return new ViewResponse(
            '@help/public/index.twig',
            [
                'title'      => $title,
                'page_title' => $title,
                'back_url'   => '/',
            ]
        );
    }
}
