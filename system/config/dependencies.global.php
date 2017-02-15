<?php

return [
    'dependencies' => [
        'factories' => [
            Johncms\Api\BbcodeInterface::class      => Johncms\Bbcode::class,
            Johncms\Api\ConfigInterface::class      => Johncms\ConfigFactory::class,
            Johncms\Api\EnvironmentInterface::class => Johncms\Environment::class,
            Johncms\Api\ToolsInterface::class       => Johncms\Tools::class,
            PDO::class                              => Johncms\PdoFactory::class,

            Johncms\User::class => Johncms\UserFactory::class,
            'counters'          => Johncms\Counters::class,
        ],

        'aliases' => [
            'bbcode' => Johncms\Api\BbcodeInterface::class,
            'tools'  => Johncms\Api\ToolsInterface::class,
        ],
    ],
];
