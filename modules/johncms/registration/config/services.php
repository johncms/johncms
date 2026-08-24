<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Registration\\Application\\',
        MODULES_PATH . 'johncms/registration/src/Application'
    )
        ->exclude([MODULES_PATH . 'johncms/registration/src/Application/DTO'])
        ->autowire()
        ->autoconfigure()
        ->public();
};
