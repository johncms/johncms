<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Homepage\\Controllers\\',
        MODULES_PATH . 'homepage/Controllers'
    )
        ->autowire()
        ->autoconfigure()
        ->public();
};
