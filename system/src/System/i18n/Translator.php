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

use Gettext\Translator as Gettext;

class Translator extends Gettext
{
    /** @var string */
    private $locale = 'ru';

    public function addTranslationDomain(string $domain, string $localesPath): void
    {
        $file = rtrim($localesPath, '/') . '/' . $this->locale . '.lng.php';

        if (is_file($file)) {
            $this->defaultDomain($domain);
            $this->loadTranslations($file);
        }
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
