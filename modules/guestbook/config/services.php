<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Guestbook\Application\Access\GuestbookAccess;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use Johncms\Modules\Guestbook\Infrastructure\Persistence\Repository\EloquentGuestbookEntryRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Guestbook\\Application\\Controllers\\',
        MODULES_PATH . 'guestbook/Application/Controllers'
    )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Guestbook\\Application\\Forms\\',
        MODULES_PATH . 'guestbook/Application/Forms'
    )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Guestbook\\Application\\Services\\',
        MODULES_PATH . 'guestbook/Application/Services'
    )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Guestbook\\Application\\UseCases\\',
        MODULES_PATH . 'guestbook/Application/UseCases'
    )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Guestbook\\Infrastructure\\',
        MODULES_PATH . 'guestbook/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(
        GuestbookEntryRepositoryInterface::class,
        EloquentGuestbookEntryRepository::class
    )->public();

    $services->set(GuestbookAccess::class)
        ->autowire()
        ->arg('$config', di('config')['johncms'])
        ->public();
};
