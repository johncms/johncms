<?php

declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (! is_file($autoload)) {
    throw new RuntimeException('Composer autoload was not found. Run composer install inside php-fpm container.');
}

/** @var Composer\Autoload\ClassLoader $composerLoader */
$composerLoader = require $autoload;

// The suite runs against the modules of the release, not against whatever the developer has
// switched off on their own installation: the functional tests drive real requests through the
// real registry, and the state file belongs to the site.
Johncms\Modules\ModuleRegistryFactory::useState(
    new Johncms\Modules\ModuleStateStore(sys_get_temp_dir() . '/johncms-tests-no-module-state.php')
);

// The suite builds containers and registries out of real modules, so their classes have to be
// findable the same way they are when the site boots.
(new Johncms\Modules\ModuleAutoloader(
    $composerLoader,
    Johncms\Modules\ModuleRegistryFactory::registry()
))->register();
