<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Guestbook\Application\Access\GuestbookAccess;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use Johncms\Modules\Guestbook\Infrastructure\Persistence\Repository\EloquentGuestbookEntryRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Guestbook\\Application\\',
        MODULES_PATH . 'guestbook/src/Application'
    )
        ->exclude(
            [
                MODULES_PATH . 'guestbook/src/Application/DTO',
                MODULES_PATH . 'guestbook/src/Application/Exceptions',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Guestbook\\Infrastructure\\',
        MODULES_PATH . 'guestbook/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(
        GuestbookEntryRepositoryInterface::class,
        EloquentGuestbookEntryRepository::class
    )->public();

    $services->set(GuestbookAccess::class)->autowire()->public();
};
