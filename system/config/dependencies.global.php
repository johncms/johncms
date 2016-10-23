<?php

return [
    'dependencies' => [
        'factories' => [
            PDO::class => Johncms\PdoFactory::class,
            'env'      => Johncms\EnvFactory::class,
            'tools'    => Johncms\ToolsFactory::class,
        ],
    ],
];
