<?php

declare(strict_types=1);

namespace Johncms\Http\Controller;

use Johncms\System\i18n\Translator;

/**
 * What a page of a module needs before it runs: the translations of that module. Templates are
 * found by convention, so nothing about them is registered here.
 */
final readonly class ControllerContext
{
    public function __construct(private Translator $translator)
    {
    }

    public function initModule(string $moduleName): void
    {
        if ($moduleName === '') {
            return;
        }

        $this->translator->addTranslationDomain(
            $moduleName,
            MODULES_PATH . $moduleName . '/locale'
        );
    }
}
