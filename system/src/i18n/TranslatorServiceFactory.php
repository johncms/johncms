<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\i18n;

use Johncms\Api\ConfigInterface;
use Johncms\Api\UserInterface;
use Psr\Container\ContainerInterface;
use Zend\I18n\Translator\Translator;

class TranslatorServiceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        // Configure the translator
        $config = $container->get('config');
        $trConfig = $config['translator'] ?? [];
        $translator = Translator::factory($trConfig);
        $translator->setLocale($this->determineLocale($container));

        if ($container->has('TranslatorPluginManager')) {
            $translator->setPluginManager($container->get('TranslatorPluginManager'));
        }

        return $translator;
    }

    private function determineLocale(ContainerInterface $container) : string
    {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        /** @var UserInterface $userConfig */
        $userConfig = $container->get(UserInterface::class)->config;

        if (isset($_POST['setlng']) && array_key_exists($_POST['setlng'], $config->lng_list)) {
            $locale = trim($_POST['setlng']);
            $_SESSION['lng'] = $locale;
        } elseif (isset($_SESSION['lng']) && array_key_exists($_SESSION['lng'], $config->lng_list)) {
            $locale = $_SESSION['lng'];
        } elseif (isset($userConfig['lng']) && array_key_exists($userConfig['lng'], $config->lng_list)) {
            $locale = $userConfig['lng'];
            $_SESSION['lng'] = $locale;
        } else {
            $locale = $config->lng;
        }

        return $locale;
    }
}
