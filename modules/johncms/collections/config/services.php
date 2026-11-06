<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Collections\Application\Api\CollectionBulkApi;
use Johncms\Modules\Collections\Application\Api\CollectionBulkApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionFileReferencesApi;
use Johncms\Modules\Collections\Application\Api\CollectionFileReferencesApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionItemRelationsApi;
use Johncms\Modules\Collections\Application\Api\CollectionItemRelationsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionLookupApi;
use Johncms\Modules\Collections\Application\Api\CollectionLookupApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApi;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApi;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApi;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsImportApi;
use Johncms\Modules\Collections\Application\Api\CollectionsImportApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsPreviewApi;
use Johncms\Modules\Collections\Application\Api\CollectionsPreviewApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsPublicApi;
use Johncms\Modules\Collections\Application\Api\CollectionsPublicApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsUserApi;
use Johncms\Modules\Collections\Application\Api\CollectionsUserApiInterface;
use Johncms\Modules\Collections\Application\Services\CollectionCacheInvalidator;
use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCache;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationRegistry;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationsSynchronizer;
use Johncms\Modules\Collections\Application\Services\CollectionFileValues;
use Johncms\Modules\Collections\Application\Services\CollectionItemFiles;
use Johncms\Modules\Collections\Application\Services\CollectionOwnerUrls;
use Johncms\Modules\Collections\Application\Services\CollectionSearchIndexer;
use Johncms\Modules\Collections\Application\Services\ReservedCodeChecker;
use Johncms\Modules\Collections\Application\Services\ReservedCodeCheckerInterface;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Domain\Repository\CollectionFileRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\CollectionSearchRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemValueRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\CollectionFileRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\CollectionSearchRepository;
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

    $services->set(CollectionBulkApiInterface::class, CollectionBulkApi::class)->autowire()->public();
    $services->set(ContentCollectionRepositoryInterface::class, ContentCollectionRepository::class)->public();
    $services->set(ContentCollectionFieldRepositoryInterface::class, ContentCollectionFieldRepository::class)->public();
    $services->set(ContentCollectionSectionRepositoryInterface::class, ContentCollectionSectionRepository::class)->public();
    $services->set(ContentCollectionItemRepositoryInterface::class, ContentCollectionItemRepository::class)->autowire()->public();
    $services->set(ContentCollectionItemValueRepositoryInterface::class, ContentCollectionItemValueRepository::class)->public();
    $services->set(CollectionOwnerUrls::class)
        ->autowire()->public()->arg('$providers', tagged_iterator('johncms.collections.owner_urls'));
    $services->set(CollectionDeclarationRegistry::class)
        ->autowire()->public()->arg('$providers', tagged_iterator('johncms.collections.declaration_provider'));
    $services->set(CollectionDeclarationsSynchronizer::class)
        ->autowire()->public()->tag('johncms.declaration_synchronizer');
    $services->set(CollectionChangeDispatcher::class)
        ->autowire()->public()->arg('$handlers', tagged_iterator('johncms.collections.change_handler'));
    $services->set(CollectionCacheInvalidator::class)
        ->autowire()->tag('johncms.collections.change_handler');
    $services->set(
        CollectionFileRepositoryInterface::class,
        CollectionFileRepository::class
    )->autowire()->public();
    $services->set(CollectionFileValues::class)
        ->autowire()->public()->tag('johncms.collections.change_handler');
    $services->set(CollectionItemFiles::class)
        ->autowire()->public()->tag('johncms.collections.change_handler');
    $services->set(
        CollectionSearchRepositoryInterface::class,
        CollectionSearchRepository::class
    )->autowire()->public();
    $services->set(CollectionSearchIndexer::class)
        ->autowire()->public()->tag('johncms.collections.change_handler');
    $services->set(CollectionCodeCacheInterface::class, CollectionCodeCache::class)->autowire()->public();
    $services->set(ReservedCodeCheckerInterface::class, ReservedCodeChecker::class)->autowire()->public();
    $services->set(
        CollectionTransactionInterface::class,
        CollectionTransaction::class
    )->autowire();
    $services->set(
        CollectionsImportApiInterface::class,
        CollectionsImportApi::class
    )->autowire()->public();
    $services->set(
        CollectionSectionsApiInterface::class,
        CollectionSectionsApi::class
    )->autowire()->public();
    $services->set(
        CollectionsPublicApiInterface::class,
        CollectionsPublicApi::class
    )->autowire()->public();
    $services->set(
        CollectionsPreviewApiInterface::class,
        CollectionsPreviewApi::class
    )->autowire()->public();
    $services->set(
        CollectionSchemaApiInterface::class,
        CollectionSchemaApi::class
    )->autowire()->public();
    $services->set(
        CollectionsUserApiInterface::class,
        CollectionsUserApi::class
    )->autowire()->public();
    $services->set(
        CollectionLookupApiInterface::class,
        CollectionLookupApi::class
    )->autowire()->public();
    $services->set(
        CollectionItemRelationsApiInterface::class,
        CollectionItemRelationsApi::class
    )->autowire()->public();
    $services->set(
        CollectionFileReferencesApiInterface::class,
        CollectionFileReferencesApi::class
    )->autowire()->public();
    $services->set(CollectionsApiInterface::class, CollectionsApi::class)->autowire()->public();
    $services->set(EnsureCollectionContentAccessUseCase::class)
        ->autowire()->public()->arg('$owners', tagged_iterator('johncms.collections.content_access'));
};
