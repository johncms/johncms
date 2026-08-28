<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Album\Domain\Repository\AlbumCommentRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumVoteRepositoryInterface;
use Johncms\Modules\Album\Infrastructure\Persistence\Repository\EloquentAlbumCommentRepository;
use Johncms\Modules\Album\Infrastructure\Persistence\Repository\EloquentAlbumPhotoRepository;
use Johncms\Modules\Album\Infrastructure\Persistence\Repository\EloquentAlbumRepository;
use Johncms\Modules\Album\Infrastructure\Persistence\Repository\EloquentAlbumVoteRepository;
use Johncms\Modules\Album\Infrastructure\Storage\AlbumPhotoStorage;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Any Symfony Console Command service is auto-registered in the CLI application.
    $services->instanceof(\Symfony\Component\Console\Command\Command::class)->tag('johncms.console_command');

    $services->load(
        'Johncms\\Modules\\Album\\Application\\',
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
        'Johncms\\Modules\\Album\\Infrastructure\\',
        dirname(__DIR__) . '/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    // Fetched at runtime with di() by classes the container does not build — an Eloquent
    // model, a legacy service, an installer. A private definition is inlined into its
    // consumers when the container is compiled and then no longer answers to a name, so
    // asking for it takes the page down; public keeps it in the container.
    $services->set(AlbumPhotoStorage::class)->autowire()->public();

    $services->set(AlbumRepositoryInterface::class, EloquentAlbumRepository::class)->public();
    $services->set(AlbumPhotoRepositoryInterface::class, EloquentAlbumPhotoRepository::class)->public();
    $services->set(AlbumVoteRepositoryInterface::class, EloquentAlbumVoteRepository::class)->public();
    $services->set(AlbumCommentRepositoryInterface::class, EloquentAlbumCommentRepository::class)->public();
};
