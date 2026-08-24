<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Contacts\Domain\Repository\ContactMessageRepositoryInterface;
use Johncms\Modules\Contacts\Infrastructure\Persistence\Repository\ContactMessageRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Contacts\\Application\\',
        MODULES_PATH . 'johncms/contacts/src/Application'
    )
        ->exclude(
            [
                MODULES_PATH . 'johncms/contacts/src/Application/DTO',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Contacts\\Infrastructure\\',
        MODULES_PATH . 'johncms/contacts/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(ContactMessageRepositoryInterface::class, ContactMessageRepository::class)->public();
};
