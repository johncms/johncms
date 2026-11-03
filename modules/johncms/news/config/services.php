<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\News\Application\Section;
use Johncms\Modules\News\Domain\Repository\NewsArticleRepositoryInterface;
use Johncms\Modules\News\Domain\Repository\NewsCommentRepositoryInterface;
use Johncms\Modules\News\Domain\Repository\NewsConfigRepositoryInterface;
use Johncms\Modules\News\Domain\Repository\NewsSectionRepositoryInterface;
use Johncms\Modules\News\Infrastructure\Config\LocalNewsConfigRepository;
use Johncms\Modules\News\Infrastructure\Persistence\Repository\EloquentNewsArticleRepository;
use Johncms\Modules\News\Infrastructure\Persistence\Repository\EloquentNewsCommentRepository;
use Johncms\Modules\News\Infrastructure\Persistence\Repository\EloquentNewsSectionRepository;

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

    $services->set(Section::class, Section::class)->public();
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
    $services->set(NewsArticleRepositoryInterface::class, EloquentNewsArticleRepository::class)->public();
    $services->set(NewsSectionRepositoryInterface::class, EloquentNewsSectionRepository::class)->public();
    $services->set(NewsCommentRepositoryInterface::class, EloquentNewsCommentRepository::class)->public();
    $services->set(NewsConfigRepositoryInterface::class, LocalNewsConfigRepository::class)->autowire()->public();
};
