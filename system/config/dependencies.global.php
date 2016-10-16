<?php

return [
    'dependencies' => [
        'factories' => [
            PDO::class => Johncms\PdoFactory::class,
            'vars'     => Johncms\VarsFactory::class,
        ],
    ],
];
