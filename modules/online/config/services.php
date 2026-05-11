<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Online\\Application\\',
        MODULES_PATH . 'online/src/Application'
    )
        ->autowire()
        ->autoconfigure()
        ->public();
};
