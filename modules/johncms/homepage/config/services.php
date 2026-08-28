<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Homepage\\Controllers\\',
        dirname(__DIR__) . '/src/Controllers'
    )
        ->autowire()
        ->autoconfigure()
        ->public();
};
