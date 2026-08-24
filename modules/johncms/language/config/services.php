<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Language\\Application\\',
        MODULES_PATH . 'johncms/language/src/Application'
    )
        ->autowire()
        ->autoconfigure()
        ->public();
};
