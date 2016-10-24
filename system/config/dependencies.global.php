<?php

return [
    'dependencies' => [
        'factories' => [
            PDO::class => Johncms\PdoFactory::class,
            'bbcode'   => Johncms\Bbcode::class,
            'env'      => Johncms\Environment::class,
            'tools'    => Johncms\Tools::class,
        ],
    ],
];
