<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\Modules\Mail\Infrastructure\Persistence\Repository\EloquentContactRepository;
use Johncms\Modules\Mail\Infrastructure\Persistence\Repository\EloquentMailMessageRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Any Symfony Console Command service is auto-registered in the CLI application.
    $services->instanceof(\Symfony\Component\Console\Command\Command::class)->tag('johncms.console_command');

    $services->load(
        'Johncms\\Modules\\Mail\\Application\\',
        MODULES_PATH . 'mail/src/Application'
    )
        ->exclude(
            [
                MODULES_PATH . 'mail/src/Application/DTO',
                MODULES_PATH . 'mail/src/Application/Exceptions',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Mail\\Infrastructure\\',
        MODULES_PATH . 'mail/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(MailMessageRepositoryInterface::class, EloquentMailMessageRepository::class)->public();
    $services->set(ContactRepositoryInterface::class, EloquentContactRepository::class)->public();
    $services->set(\Johncms\Modules\Mail\Application\Services\MailFileService::class)->public();
};
