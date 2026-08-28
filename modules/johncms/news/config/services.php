<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\News\Application\Section;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\News\\Application\\',
        dirname(__DIR__) . '/src/Application'
    )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(Section::class, Section::class)->public();
};
