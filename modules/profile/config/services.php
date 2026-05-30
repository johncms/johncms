<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Modules\Profile\Infrastructure\Persistence\Repository\EloquentKarmaRepository;
use Johncms\Modules\Profile\Infrastructure\Persistence\Repository\ProfileUserRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Any Symfony Console Command service is auto-registered in the CLI application.
    $services->instanceof(\Symfony\Component\Console\Command\Command::class)->tag('johncms.console_command');

    $services->load(
        'Johncms\\Modules\\Profile\\Application\\',
        MODULES_PATH . 'profile/src/Application'
    )
        ->exclude(
            [
                MODULES_PATH . 'profile/src/Application/DTO',
                MODULES_PATH . 'profile/src/Application/Exceptions',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Profile\\Infrastructure\\',
        MODULES_PATH . 'profile/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(ProfileUserRepositoryInterface::class, ProfileUserRepository::class)->public();
    $services->set(KarmaRepositoryInterface::class, EloquentKarmaRepository::class)->public();
};
