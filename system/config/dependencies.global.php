<?php

return [
    'dependencies' => [
        'factories' => [
            PDO::class => Johncms\PdoFactory::class,
            'bbcode'   => Johncms\Bbcode::class,
            'counters' => Johncms\Counters::class,
            'env'      => Johncms\Environment::class,
            'tools'    => Johncms\Tools::class,
        ],
    ],
];
