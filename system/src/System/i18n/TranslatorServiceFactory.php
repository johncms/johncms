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
    /**
     * The translator of the site, speaking the language the site is configured in.
     *
     * Who is visiting is not asked here: this runs at boot, before there is a database
     * connection, and the language of a signed-in visitor is a query away. The kernel applies
     * the locale of the visitor on top of this one, for every request it serves.
     */
    public function __invoke(): Translator
    {
        $translator = new Translator();
        $translator->setLocale((string) config('johncms.lng', 'en'));

        return $translator;
    }
}
