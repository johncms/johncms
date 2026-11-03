<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\News\Domain\Repository\NewsConfigRepositoryInterface;
use Johncms\Modules\News\Infrastructure\Config\LocalNewsConfigRepository;

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

    $services->set(\Johncms\Modules\News\Application\Collections\NewsCollectionAvailability::class)->autowire()->public()->tag('johncms.collections.content_access');
    $services->set(\Johncms\Modules\News\Application\Collections\NewsCollectionDeclaration::class)
        ->autowire()->public()->tag('johncms.collections.declaration_provider');
    $services->set(\Johncms\Modules\News\Application\Collections\NewsCollectionOwnerUrls::class)
        ->autowire()->public()->tag('johncms.collections.owner_urls');
    $services->set(
        \Johncms\Modules\News\Application\Collections\NewsReadServiceInterface::class,
        \Johncms\Modules\News\Application\Collections\NewsReadService::class
    )->autowire()->public();
    $services->set(
        \Johncms\Modules\News\Application\Collections\NewsViewMapperInterface::class,
        \Johncms\Modules\News\Application\Collections\NewsViewMapper::class
    )->autowire()->public();
    $services->set(
        \Johncms\Modules\News\Application\Collections\NewsDiscussionServiceInterface::class,
        \Johncms\Modules\News\Application\Collections\NewsDiscussionService::class
    )->autowire()->public();
    $services->set(
        \Johncms\Modules\News\Domain\Repository\NewsDiscussionRepositoryInterface::class,
        \Johncms\Modules\News\Infrastructure\Persistence\Repository\EloquentNewsDiscussionRepository::class
    )->autowire()->public();
    $services->set(\Johncms\Modules\News\Application\Collections\NewsCollectionChangeHandler::class)
        ->autowire()->tag('johncms.collections.change_handler');
    $services->set(\Johncms\Modules\News\Domain\Repository\NewsLegacyRepositoryInterface::class, \Johncms\Modules\News\Infrastructure\Persistence\Repository\SqlNewsLegacyRepository::class)->autowire()->public();
    $services->set(\Johncms\Modules\News\Domain\Repository\NewsLegacyStorageRepositoryInterface::class, \Johncms\Modules\News\Infrastructure\Persistence\Repository\SqlNewsLegacyStorageRepository::class)->autowire()->public();
    $services->set(NewsConfigRepositoryInterface::class, LocalNewsConfigRepository::class)->autowire()->public();
};
