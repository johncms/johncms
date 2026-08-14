<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Login\\Application\\',
        MODULES_PATH . 'login/src/Application'
    )
        ->autowire()
        ->autoconfigure()
        ->public();
};
