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

use Johncms\System\Http\Request;
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

        // Configure the translator
        $config = $container->get('config');

        $translator = new Translator();
        $translator->setLocale(
            $this->determineLocale(
                $userConfig->lng,
                $config['johncms']['lng'] ?? 'en',
                $config['johncms']['lng_list'] ?? [],
                $request->getPost('setlng')
            )
        );

        return $translator;
    }

    private function determineLocale(string $userLng, string $systemLng, array $lngList, string $setLng = null): string
    {
        if (null !== $setLng && array_key_exists($setLng, $lngList)) {
            $locale = trim($setLng);
            $_SESSION['lng'] = $locale;
        } elseif (isset($_SESSION['lng']) && array_key_exists($_SESSION['lng'], $lngList)) {
            $locale = $_SESSION['lng'];
        } elseif (array_key_exists($userLng, $lngList)) {
            $locale = $userLng;
            $_SESSION['lng'] = $locale;
        } else {
            $locale = $systemLng;
        }

        return $locale;
    }
}
