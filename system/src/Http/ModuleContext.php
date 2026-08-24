<?php

declare(strict_types=1);

namespace Johncms\Http;

use Johncms\Modules\ModuleRegistry;
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
    public function __construct(
        private Translator $translator,
        private ModuleRegistry $registry,
    ) {
    }

    /**
     * The module arrives as its key — `johncms/forum`. The domain is its alias, because that is
     * what `d__('forum', …)` says in the code and what the dictionaries are generated for, and the
     * registry is what knows the alias: a module keeps the one it was installed under, whatever
     * its manifest says today.
     */
    public function enter(?string $module): void
    {
        if ($module === null || $module === '') {
            return;
        }

        $state = $this->registry->find($module);

        // Only a loaded module has routes, so this cannot happen while one is being served. It
        // can while something else calls in — and a page without its own domain still renders,
        // in the system one.
        if ($state === null || $state->manifest === null) {
            return;
        }

        $this->translator->addTranslationDomain(
            $state->alias,
            $state->manifest->path . DIRECTORY_SEPARATOR . 'locale'
        );
    }
}
