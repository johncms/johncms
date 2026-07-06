<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionSectionRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Collections\\Application\\',
        MODULES_PATH . 'collections/src/Application'
    )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Collections\\Infrastructure\\',
        MODULES_PATH . 'collections/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(ContentCollectionRepositoryInterface::class, ContentCollectionRepository::class)->public();
    $services->set(ContentCollectionFieldRepositoryInterface::class, ContentCollectionFieldRepository::class)->public();
    $services->set(ContentCollectionSectionRepositoryInterface::class, ContentCollectionSectionRepository::class)->public();
    $services->set(ContentCollectionItemRepositoryInterface::class, ContentCollectionItemRepository::class)->autowire()->public();
};
