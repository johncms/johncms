<?php

declare(strict_types=1);

namespace Johncms\Http;

use Johncms\System\i18n\Translator;

/**
 * What a page needs before it runs because of the module it belongs to: the translations of that
 * module, which also become the default domain, so `__()` in the controller and in its templates
 * resolves against them.
 *
 * Entered once per request by the kernel, from the module stamped on the matched route. It is a
 * fact of the request rather than of the controller: a controller is one object serving many
 * requests, and setting the domain from its constructor would leave every request after the first
 * one rendering with the domain of whoever was constructed last.
 */
final readonly class ModuleContext
{
    public function __construct(private Translator $translator)
    {
    }

    public function enter(?string $module): void
    {
        if ($module === null || $module === '') {
            return;
        }

        $this->translator->addTranslationDomain($module, MODULES_PATH . $module . '/locale');
    }
}
