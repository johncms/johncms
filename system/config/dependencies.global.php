<?php

return [
    'dependencies' => [
        'factories' => [
            PDO::class => Mobicms\Database\PdoFactory::class,
        ],
    ],
];
