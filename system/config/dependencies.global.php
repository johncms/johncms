<?php

return [
    'dependencies' => [
        'factories' => [
            Johncms\Api\BbcodeInterface::class => Johncms\Bbcode::class,
            Johncms\Api\ToolsInterface::class  => Johncms\Tools::class,
            Johncms\Cleanup::class             => Johncms\Cleanup::class,
            PDO::class                         => Johncms\PdoFactory::class,
            Johncms\Config::class              => Johncms\ConfigFactory::class,
            Johncms\User::class                => Johncms\UserFactory::class,
            'counters'                         => Johncms\Counters::class,
            'env'                              => Johncms\Environment::class,
        ],

        'aliases' => [
            'bbcode' => Johncms\Api\BbcodeInterface::class,
            'tools'  => Johncms\Api\ToolsInterface::class,
        ],
    ],
];
