<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Modules\Forum\Infrastructure\Persistence\Repository\ForumMessageRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Forum\\Application\\',
        MODULES_PATH . 'forum/src/Application'
    )
        ->exclude(
            [
                MODULES_PATH . 'forum/src/Application/DTO',
                MODULES_PATH . 'forum/src/Application/Exceptions',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Forum\\Infrastructure\\',
        MODULES_PATH . 'forum/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(ForumMessageRepositoryInterface::class, ForumMessageRepository::class)->public();
};
