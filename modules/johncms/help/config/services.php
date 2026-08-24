<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Help\\Application\\',
        MODULES_PATH . 'johncms/help/src/Application'
    )
        ->autowire()
        ->autoconfigure()
        ->public();
};
