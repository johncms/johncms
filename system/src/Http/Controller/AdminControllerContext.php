<?php

declare(strict_types=1);

namespace Johncms\Http\Controller;

use Johncms\NavChain;
use Johncms\System\i18n\Translator;

/**
 * What every page of the panel needs before it runs: the translations of its module and the
 * first link of the navigation chain.
 */
final readonly class AdminControllerContext
{
    public function __construct(
        private Translator $translator,
        private NavChain $navChain,
    ) {
    }

    public function initModule(string $moduleName = ''): void
    {
        if ($moduleName !== '') {
            $this->translator->addTranslationDomain(
                $moduleName,
                MODULES_PATH . $moduleName . '/locale'
            );
        }

        $this->translator->addTranslationDomain('admin', MODULES_PATH . 'admin/locale', false);

        $this->navChain->add(d__('admin', 'Admin Panel'), '/admin/');
    }
}
