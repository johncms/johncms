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
use Johncms\System\Users\User;
use Johncms\System\Users\UserConfig;
use Psr\Container\ContainerInterface;

class TranslatorServiceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        /** @var Request $request */
        $request = $container->get(Request::class);

        /** @var UserConfig $userConfig */
        $userConfig = $container->get(User::class)->config;

        $session = $container->get(Session::class);

        // Configure the translator
        $config = config('johncms', []);

        $translator = new Translator();
        $translator->setLocale(
            $this->determineLocale(
                $userConfig->lng,
                $config['lng'] ?? 'en',
                $config['lng_list'] ?? [],
                // Read from the query only: this factory runs during boot, and reading the body
                // would decode a JSON payload there — an unparsable one then killed the whole
                // boot with an uncaught JsonException, before any error handler was registered.
                $request->query->getString('setlng') ?: null,
                $session
            )
        );

        return $translator;
    }

    private function determineLocale(string $userLng, string $systemLng, array $lngList, ?string $setLng = null, ?Session $session = null): string
    {
        if (null !== $setLng && array_key_exists($setLng, $lngList)) {
            $locale = trim($setLng);
            $session?->set('lng', $locale);
        } elseif ($session?->has('lng') && array_key_exists($session->get('lng'), $lngList)) {
            $locale = $session->get('lng');
        } elseif (array_key_exists($userLng, $lngList)) {
            $locale = $userLng;
            $session?->set('lng', $locale);
        } else {
            $locale = $systemLng;
        }

        return $locale;
    }
}
