<?php

declare(strict_types=1);

namespace Johncms\Http;

use Johncms\NavChain;
use Johncms\System\i18n\Translator;

/**
 * What a page of the admin panel needs on top of its module context: the translations of the panel
 * itself, which its layout and menu are written in, and the first link of the navigation chain.
 *
 * The panel domain is registered without becoming the default one — a page of the panel still
 * writes `__()` for the strings of its own module and names this one explicitly as `d__('admin')`.
 *
 * Entered by the access guards of the panel, so a page is in the panel exactly when a guard let it
 * in. The two routes declared outside those guards (the login screen and the system check) enter
 * it themselves.
 */
final readonly class AdminAreaContext
{
    public function __construct(
        private Translator $translator,
        private NavChain $navChain,
    ) {
    }

    public function enter(): void
    {
        $this->translator->addTranslationDomain('admin', MODULES_PATH . 'johncms/admin/locale', false);

        $this->navChain->add(d__('admin', 'Admin Panel'), '/admin/');
    }
}
