<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Notifications\Domain\Repository\NotificationRepositoryInterface;
use Johncms\Modules\Notifications\Infrastructure\Persistence\Repository\EloquentNotificationRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Notifications\\Application\\',
        MODULES_PATH . 'johncms/notifications/src/Application'
    )
        ->exclude(
            [
                MODULES_PATH . 'johncms/notifications/src/Application/DTO',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Notifications\\Infrastructure\\',
        MODULES_PATH . 'johncms/notifications/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(NotificationRepositoryInterface::class, EloquentNotificationRepository::class)->public();
};
