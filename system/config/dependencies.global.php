<?php

return [
    'dependencies' => [
        'factories' => [
            Johncms\Api\BbcodeInterface::class      => Johncms\Bbcode::class,
            Johncms\Api\ConfigInterface::class      => Johncms\ConfigFactory::class,
            Johncms\Api\EnvironmentInterface::class => Johncms\Environment::class,
            Johncms\Api\ToolsInterface::class       => Johncms\Tools::class,
            Johncms\Api\UserInterface::class        => Johncms\UserFactory::class,
            PDO::class                              => Johncms\PdoFactory::class,

            'counters' => Johncms\Counters::class,
        ],

        // DEPRECATED!!!
        // Данные псевдонимы запрещены к использованию и будут удалены в ближайших версиях.
        // В своих разработках используйте вызов соответствующих интерфейсов
        'aliases' => [
            Johncms\User::class => Johncms\Api\UserInterface::class,
            'bbcode'            => Johncms\Api\BbcodeInterface::class,
            'env'               => Johncms\Api\EnvironmentInterface::class,
            'tools'             => Johncms\Api\ToolsInterface::class,
        ],
    ],
];
