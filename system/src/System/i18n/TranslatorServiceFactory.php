<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\i18n;

class TranslatorServiceFactory
{
    public function __construct(
        private readonly LocaleResolver $localeResolver,
    ) {
    }

    /**
     * The locale of the boot request. The translator is shared, so the kernel then applies the
     * locale of every request it serves on top of this one.
     */
    public function __invoke(): Translator
    {
        $translator = new Translator();
        $translator->setLocale($this->localeResolver->resolve());

        return $translator;
    }
}
