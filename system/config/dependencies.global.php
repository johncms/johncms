<?php

return [
    'dependencies' => [
        'factories' => [
            PDO::class => Johncms\Database\PdoFactory::class,
        ],
    ],
];
