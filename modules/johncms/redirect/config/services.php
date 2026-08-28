<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Redirect\Domain\Repository\AdsRepositoryInterface;
use Johncms\Modules\Redirect\Infrastructure\Persistence\Repository\EloquentAdsRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Redirect\\Application\\',
        dirname(__DIR__) . '/src/Application'
    )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(AdsRepositoryInterface::class, EloquentAdsRepository::class)->public();
};
