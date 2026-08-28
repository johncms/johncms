<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Online\Domain\Repository\OnlineGuestRepositoryInterface;
use Johncms\Modules\Online\Domain\Repository\OnlineUserRepositoryInterface;
use Johncms\Modules\Online\Infrastructure\Persistence\Repository\EloquentOnlineGuestRepository;
use Johncms\Modules\Online\Infrastructure\Persistence\Repository\EloquentOnlineUserRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Online\\Application\\',
        dirname(__DIR__) . '/src/Application'
    )
        ->exclude(
            [
                dirname(__DIR__) . '/src/Application/DTO',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Online\\Infrastructure\\',
        dirname(__DIR__) . '/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(
        OnlineUserRepositoryInterface::class,
        EloquentOnlineUserRepository::class
    )->public();

    $services->set(
        OnlineGuestRepositoryInterface::class,
        EloquentOnlineGuestRepository::class
    )->public();
};
