<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Collections\Application\Api\CollectionsApi;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCache;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Application\Services\ReservedCodeChecker;
use Johncms\Modules\Collections\Application\Services\ReservedCodeCheckerInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemValueRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemValueRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionSectionRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Collections\\Application\\',
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
        'Johncms\\Modules\\Collections\\Infrastructure\\',
        dirname(__DIR__) . '/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(ContentCollectionRepositoryInterface::class, ContentCollectionRepository::class)->public();
    $services->set(ContentCollectionFieldRepositoryInterface::class, ContentCollectionFieldRepository::class)->public();
    $services->set(ContentCollectionSectionRepositoryInterface::class, ContentCollectionSectionRepository::class)->public();
    $services->set(ContentCollectionItemRepositoryInterface::class, ContentCollectionItemRepository::class)->autowire()->public();
    $services->set(ContentCollectionItemValueRepositoryInterface::class, ContentCollectionItemValueRepository::class)->public();
    $services->set(CollectionCodeCacheInterface::class, CollectionCodeCache::class)->autowire()->public();
    $services->set(ReservedCodeCheckerInterface::class, ReservedCodeChecker::class)->autowire()->public();
    $services->set(CollectionsApiInterface::class, CollectionsApi::class)->autowire()->public();
};
