<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\i18n;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Users\User;
use Psr\Container\ContainerInterface;

class TranslatorServiceFactory
{
    public function __invoke(ContainerInterface $container): Translator
    {
        /** @var Request $request */
        $request = $container->get(Request::class);

        $userLng = $container->get(User::class)?->settings ?? '';

        // Configure the translator
        $config = $container->get('config');

        $translator = new Translator();
        $translator->setLocale(
            $this->determineLocale(
                $userLng,
                $config['johncms']['lng'] ?? 'en',
                $config['johncms']['lng_list'] ?? [],
                $request->getPost('setlng')
            )
        );

        return $translator;
    }

    private function determineLocale(string $userLng, string $systemLng, array $lngList, string $setLng = null): string
    {
        $session = di(Session::class);
        if (null !== $setLng && array_key_exists($setLng, $lngList)) {
            $locale = trim($setLng);
            $session->set('lng', $locale);
        } elseif ($session->has('lng') && array_key_exists($session->get('lng'), $lngList)) {
            $locale = $session->get('lng');
        } elseif (array_key_exists($userLng, $lngList)) {
            $locale = $userLng;
            $session->set('lng', $locale);
        } else {
            $locale = $systemLng;
        }

        return $locale;
    }
}
