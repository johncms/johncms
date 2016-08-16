<?php

return [
    'cache' => [
        'adapter' => [
            'name'    => 'filesystem',
            'options' => [
                'cache_dir' => ROOT_PATH . 'files/cache/',
                'ttl'       => 3600,
            ],
        ],

        'plugins' => [
            'serializer',
            'exception_handler' => [
                'throw_exceptions' => false, // Don't throw exceptions on cache errors
            ],
        ],
    ],

    'dependencies' => [
        'factories' => [
            Zend\Cache\Storage\StorageInterface::class     => Zend\Cache\Service\StorageCacheFactory::class,
            Zend\Cache\PatternPluginManager::class         => Zend\Cache\Service\PatternPluginManagerFactory::class,
            Zend\Cache\Storage\AdapterPluginManager::class => Zend\Cache\Service\StorageAdapterPluginManagerFactory::class,
            Zend\Cache\Storage\PluginManager::class        => Zend\Cache\Service\StoragePluginManagerFactory::class,
        ],
    ],
];
