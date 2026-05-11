<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Library\\Application\\',
        MODULES_PATH . 'library/src/Application'
    )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Library\\Infrastructure\\',
        MODULES_PATH . 'library/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();
};
