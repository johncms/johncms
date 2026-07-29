<?php

declare(strict_types=1);

namespace Johncms\Modules\Language\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Request;
use Johncms\System\View\Render;

final readonly class LanguageController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
    ) {
        $this->controllerContext->initModule('language');
    }

    public function __invoke(Request $request): string
    {
        if ($request->getMethod() === 'POST') {
            return '';
        }

        return $this->render->render('language::index');
    }
}
