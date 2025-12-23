<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'News\\Controllers\\',
        MODULES_PATH . 'news/Controllers'
    )
        ->autowire()
        ->autoconfigure()
        ->public();
};
