<?php

declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (! is_file($autoload)) {
    throw new RuntimeException('Composer autoload was not found. Run composer install inside php-fpm container.');
}

/** @var Composer\Autoload\ClassLoader $composerLoader */
$composerLoader = require $autoload;

// The suite builds containers and registries out of real modules, so their classes have to be
// findable the same way they are when the site boots.
(new Johncms\Modules\ModuleAutoloader(
    $composerLoader,
    Johncms\Modules\ModuleRegistryFactory::registry()
))->register();
