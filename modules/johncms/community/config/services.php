<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Community\Domain\Repository\CommunityUserRepositoryInterface;
use Johncms\Modules\Community\Infrastructure\Persistence\Repository\CommunityUserRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Community\\Application\\',
        MODULES_PATH . 'johncms/community/src/Application'
    )
        ->exclude(
            [
                MODULES_PATH . 'johncms/community/src/Application/DTO',
            ]
        )
        ->bind(
            '$counters',
            service('counters')
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Community\\Infrastructure\\',
        MODULES_PATH . 'johncms/community/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(CommunityUserRepositoryInterface::class, CommunityUserRepository::class)->public();
};
