<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\News\Application\Section;
use Johncms\Modules\News\Domain\Repository\NewsArticleRepositoryInterface;
use Johncms\Modules\News\Domain\Repository\NewsSectionRepositoryInterface;
use Johncms\Modules\News\Infrastructure\Persistence\Repository\EloquentNewsArticleRepository;
use Johncms\Modules\News\Infrastructure\Persistence\Repository\EloquentNewsSectionRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\News\\Application\\',
        dirname(__DIR__) . '/src/Application'
    )
        ->exclude(
            [
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
    $services->set(NewsArticleRepositoryInterface::class, EloquentNewsArticleRepository::class)->public();
    $services->set(NewsSectionRepositoryInterface::class, EloquentNewsSectionRepository::class)->public();
};
