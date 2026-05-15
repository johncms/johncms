<?php

declare(strict_types=1);

namespace Johncms\Http\Controller;

use Johncms\System\i18n\Translator;
use Johncms\System\View\Render;

final readonly class ControllerContext
{
    public function __construct(
        private Render $render,
        private Translator $translator,
    ) {
    }

    public function initModule(string $moduleName): void
    {
        if ($moduleName === '') {
            return;
        }

        $this->render->addFolder(
            $moduleName,
            MODULES_PATH . $moduleName . '/templates/'
        );

        $this->translator->addTranslationDomain(
            $moduleName,
            MODULES_PATH . $moduleName . '/locale'
        );
    }
}
