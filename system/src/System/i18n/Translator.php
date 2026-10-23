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

use Closure;
use Gettext\Translator as Gettext;

class Translator extends Gettext
{
    /**
     * The domain of the CMS itself, the one every string outside a module is written in.
     *
     * A constant because it is a name held by the core, and a module may not take it: the registry
     * refuses an alias that names it, the way it refuses an alias another module already holds.
     */
    public const string SYSTEM_DOMAIN = 'system';

    /** @var string */
    private $locale = 'ru';

    /**
     * The registered domains, in registration order: name => [path, whether it became the
     * default one]. Kept so a change of locale can reload them, see setLocale().
     *
     * @var array<string, array{path: string, default: bool}>
     */
    private array $domains = [];

    /** @var (Closure(string): ?string)|null */
    private ?Closure $domainLocator = null;

    /** @var array<string, true> The domains the locator knew nothing of, not to be asked again. */
    private array $unlocated = [];

    /**
     * Where to look for the dictionaries of a domain nobody has registered: the directory of its
     * locales, or null when there is no such domain.
     *
     * A module shows its messages beyond its own pages — a place in "who is online", an item of a
     * menu, a block of the home page — and the domain of a module is registered only on its own
     * routes. With a locator, the dictionary of a domain is loaded the first time a message of it
     * is asked for, wherever that happens.
     *
     * @param Closure(string): ?string $locator
     */
    public function locateDomainsWith(Closure $locator): void
    {
        $this->domainLocator = $locator;
    }

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

    protected function getTranslation(?string $domain, ?string $context, string $original): ?array
    {
        if ($domain !== null && ! isset($this->domains[$domain])) {
            $this->registerLocatedDomain($domain);
        }

        return parent::getTranslation($domain, $context, $original);
    }

    private function registerLocatedDomain(string $domain): void
    {
        if ($this->domainLocator === null || isset($this->unlocated[$domain])) {
            return;
        }

        $localesPath = ($this->domainLocator)($domain);

        if ($localesPath === null) {
            $this->unlocated[$domain] = true;

            return;
        }

        $this->addTranslationDomain($domain, $localesPath, false);
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
