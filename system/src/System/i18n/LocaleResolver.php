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

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Users\User;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The language of the request being served: the ?setlng parameter, then what the visitor picked
 * earlier in this session, then the language of their profile, then the system default.
 *
 * A service of its own rather than a step of TranslatorServiceFactory: the translator is shared
 * and outlives the request, so the locale has to be recomputed for every request instead of being
 * fixed at the moment the translator happens to be built.
 */
final readonly class LocaleResolver
{
    public function __construct(
        private RequestStack $requestStack,
        private Session $session,
        private User $user,
    ) {
    }

    public function resolve(): string
    {
        $config = config('johncms', []);
        $systemLocale = $config['lng'] ?? 'en';
        $localeList = $config['lng_list'] ?? [];

        // Read from the query only: this also runs during boot, and reading the body would decode
        // a JSON payload there — an unparsable one then killed the whole boot with an uncaught
        // JsonException, before any error handler was registered.
        $requestedLocale = $this->request()->query->getString('setlng') ?: null;

        if ($requestedLocale !== null && array_key_exists($requestedLocale, $localeList)) {
            $locale = trim($requestedLocale);
            $this->session->set('lng', $locale);

            return $locale;
        }

        if ($this->session->has('lng') && array_key_exists($this->session->get('lng'), $localeList)) {
            return $this->session->get('lng');
        }

        $userLocale = $this->user->config->lng;

        if (array_key_exists($userLocale, $localeList)) {
            $this->session->set('lng', $userLocale);

            return $userLocale;
        }

        return $systemLocale;
    }

    private function request(): Request
    {
        $request = $this->requestStack->getCurrentRequest();

        if (! $request instanceof Request) {
            throw new RuntimeException('No request is being served: the request stack is empty.');
        }

        return $request;
    }
}
