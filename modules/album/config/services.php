<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Any Symfony Console Command service is auto-registered in the CLI application.
    $services->instanceof(\Symfony\Component\Console\Command\Command::class)->tag('johncms.console_command');

    $services->load(
        'Johncms\\Modules\\Album\\Application\\',
        MODULES_PATH . 'album/src/Application'
    )
        ->exclude(
            [
                MODULES_PATH . 'album/src/Application/DTO',
                MODULES_PATH . 'album/src/Application/Exceptions',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Album\\Infrastructure\\',
        MODULES_PATH . 'album/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();
};
