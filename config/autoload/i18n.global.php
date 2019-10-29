<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\i18n\TranslatorServiceFactory;
use Zend\I18n\Translator;

return [
    'translator' => [
        'translation_file_patterns' => [
            [
                'type'        => 'gettext',
                'base_dir'    => ROOT_PATH . 'system/locale',
                'pattern'     => '/%s/system.mo',
                'text_domain' => 'system',
            ],
        ],
    ],

    'dependencies' => [
        'aliases' => [
            'TranslatorPluginManager'             => Translator\LoaderPluginManager::class,
            Translator\TranslatorInterface::class => Translator\Translator::class,
        ],

        'factories' => [
            Translator\Translator::class          => TranslatorServiceFactory::class,
            Translator\LoaderPluginManager::class => Translator\LoaderPluginManagerFactory::class,
        ],
    ],
];
