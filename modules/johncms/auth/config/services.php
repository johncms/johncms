<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Auth\Domain\Repository\AuthUserRepositoryInterface;
use Johncms\Modules\Auth\Infrastructure\Persistence\Repository\EloquentAuthUserRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Auth\\Application\\',
        MODULES_PATH . 'johncms/auth/src/Application'
    )
        ->exclude(
            [
                MODULES_PATH . 'johncms/auth/src/Application/DTO',
                MODULES_PATH . 'johncms/auth/src/Application/Exceptions',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Auth\\Infrastructure\\',
        MODULES_PATH . 'johncms/auth/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(AuthUserRepositoryInterface::class, EloquentAuthUserRepository::class)->public();
};
