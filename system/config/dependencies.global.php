<?php

return [
    'dependencies' => [
        'factories' => [
            Johncms\Cleanup::class => Johncms\Cleanup::class,
            PDO::class            => Johncms\PdoFactory::class,
            Johncms\Config::class => Johncms\ConfigFactory::class,
            Johncms\User::class   => Johncms\UserFactory::class,
            'bbcode'              => Johncms\Bbcode::class,
            'counters'            => Johncms\Counters::class,
            'env'                 => Johncms\Environment::class,
            'tools'               => Johncms\Tools::class,
        ],
    ],
];
