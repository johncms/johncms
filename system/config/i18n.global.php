<?php

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
            Translator\Translator::class          => Translator\TranslatorServiceFactory::class,
            Translator\LoaderPluginManager::class => Translator\LoaderPluginManagerFactory::class,
        ],
    ],
];
