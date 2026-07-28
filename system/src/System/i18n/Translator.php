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

    /**
     * The registered domains, in registration order: name => [path, whether it became the
     * default one]. Kept so a change of locale can reload them, see setLocale().
     *
     * @var array<string, array{path: string, default: bool}>
     */
    private array $domains = [];

    public function addTranslationDomain(string $domain, string $localesPath, bool $set_default = true): void
    {
        $this->domains[$domain] = ['path' => $localesPath, 'default' => $set_default];

        $this->loadDomain($domain, $localesPath, $set_default);
    }

    /**
     * Switching locales reloads the translations of every registered domain.
     *
     * Domains are registered from the constructors of the controllers, which are shared, so they
     * are registered once per process while the locale belongs to the request. Only remembering
     * the new locale would leave the dictionary holding the messages of the previous one — the
     * page would then answer in the visitor's language for whatever was loaded afterwards and in
     * the previous visitor's language for everything else.
     */
    public function setLocale(string $locale): void
    {
        if ($locale === $this->locale) {
            return;
        }

        $this->locale = $locale;

        if ($this->domains === []) {
            return;
        }

        $defaultDomain = $this->domain;
        $this->dictionary = [];
        $this->plurals = [];
        $this->domain = null;

        foreach ($this->domains as $domain => $registration) {
            $this->loadDomain($domain, $registration['path'], $registration['default']);
        }

        if ($defaultDomain !== null) {
            $this->defaultDomain($defaultDomain);
        }
    }

    private function loadDomain(string $domain, string $localesPath, bool $setDefault): void
    {
        $file = rtrim($localesPath, '/') . '/' . $this->locale . '.lng.php';

        if (is_file($file)) {
            if ($setDefault) {
                $this->defaultDomain($domain);
            }
            $this->loadTranslations($file);
        }
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
