<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\News\Application\Collections\NewsCollectionAvailability;
use Johncms\Modules\News\Application\Collections\NewsCollectionChangeHandler;
use Johncms\Modules\News\Application\Collections\NewsCollectionDeclaration;
use Johncms\Modules\News\Application\Collections\NewsCollectionOwnerUrls;
use Johncms\Modules\News\Application\Collections\NewsDiscussionService;
use Johncms\Modules\News\Application\Collections\NewsDiscussionServiceInterface;
use Johncms\Modules\News\Application\Collections\NewsReadService;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Modules\News\Application\Collections\NewsViewMapper;
use Johncms\Modules\News\Application\Collections\NewsViewMapperInterface;
use Johncms\Modules\News\Domain\Repository\NewsConfigRepositoryInterface;
use Johncms\Modules\News\Domain\Repository\NewsDiscussionRepositoryInterface;
use Johncms\Modules\News\Domain\Repository\NewsLegacyRepositoryInterface;
use Johncms\Modules\News\Domain\Repository\NewsLegacyStorageRepositoryInterface;
use Johncms\Modules\News\Infrastructure\Config\LocalNewsConfigRepository;
use Johncms\Modules\News\Infrastructure\Persistence\Repository\EloquentNewsDiscussionRepository;
use Johncms\Modules\News\Infrastructure\Persistence\Repository\SqlNewsLegacyRepository;
use Johncms\Modules\News\Infrastructure\Persistence\Repository\SqlNewsLegacyStorageRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\News\\Application\\',
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
        'Johncms\\Modules\\News\\Infrastructure\\',
        dirname(__DIR__) . '/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(NewsCollectionAvailability::class)->autowire()->public()->tag('johncms.collections.content_access');
    $services->set(NewsCollectionDeclaration::class)
        ->autowire()->public()->tag('johncms.collections.declaration_provider');
    $services->set(NewsCollectionOwnerUrls::class)
        ->autowire()->public()->tag('johncms.collections.owner_urls');
    $services->set(
        NewsReadServiceInterface::class,
        NewsReadService::class
    )->autowire()->public();
    $services->set(
        NewsViewMapperInterface::class,
        NewsViewMapper::class
    )->autowire()->public();
    $services->set(
        NewsDiscussionServiceInterface::class,
        NewsDiscussionService::class
    )->autowire()->public();
    $services->set(
        NewsDiscussionRepositoryInterface::class,
        EloquentNewsDiscussionRepository::class
    )->autowire()->public();
    $services->set(NewsCollectionChangeHandler::class)
        ->autowire()->tag('johncms.collections.change_handler');
    $services->set(NewsLegacyRepositoryInterface::class, SqlNewsLegacyRepository::class)->autowire()->public();
    $services->set(NewsLegacyStorageRepositoryInterface::class, SqlNewsLegacyStorageRepository::class)->autowire()->public();
    $services->set(NewsConfigRepositoryInterface::class, LocalNewsConfigRepository::class)->autowire()->public();
};
