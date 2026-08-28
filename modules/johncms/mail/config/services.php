<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\Modules\Mail\Infrastructure\Persistence\Repository\EloquentContactRepository;
use Johncms\Modules\Mail\Infrastructure\Persistence\Repository\EloquentMailMessageRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();


    $services->load(
        'Johncms\\Modules\\Mail\\Application\\',
        dirname(__DIR__) . '/src/Application'
    )
        ->exclude(
            [
                dirname(__DIR__) . '/src/Application/DTO',
                dirname(__DIR__) . '/src/Application/Exceptions',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Mail\\Infrastructure\\',
        dirname(__DIR__) . '/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(MailMessageRepositoryInterface::class, EloquentMailMessageRepository::class)->public();
    $services->set(ContactRepositoryInterface::class, EloquentContactRepository::class)->public();
    $services->set(\Johncms\Modules\Mail\Application\Services\MailFileService::class)->public();
};
