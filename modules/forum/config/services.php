<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Forum\\Application\\',
        MODULES_PATH . 'forum/src/Application'
    )
        ->exclude(
            [
                MODULES_PATH . 'forum/src/Application/DTO',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Forum\\Infrastructure\\',
        MODULES_PATH . 'forum/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();
};
