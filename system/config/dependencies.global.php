<?php

return [
    'dependencies' => [
        'factories' => [
            PDO::class => Johncms\PdoFactory::class,
            'env'      => Johncms\Environment::class,
            'tools'    => Johncms\Tools::class,
        ],
    ],
];
