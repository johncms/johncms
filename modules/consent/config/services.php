<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Consent\Domain\Repository\ConsentLogRepositoryInterface;
use Johncms\Modules\Consent\Domain\Repository\ConsentRepositoryInterface;
use Johncms\Modules\Consent\Infrastructure\Persistence\Repository\ConsentLogRepository;
use Johncms\Modules\Consent\Infrastructure\Persistence\Repository\ConsentRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Consent\\Application\\',
        MODULES_PATH . 'consent/src/Application'
    )
        ->exclude(
            [
                MODULES_PATH . 'consent/src/Application/DTO',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Consent\\Infrastructure\\',
        MODULES_PATH . 'consent/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(ConsentRepositoryInterface::class, ConsentRepository::class)->public();
    $services->set(ConsentLogRepositoryInterface::class, ConsentLogRepository::class)->public();
};
