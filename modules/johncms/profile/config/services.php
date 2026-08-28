<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Profile\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\BanRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\IpHistoryRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileActivityRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Modules\Profile\Infrastructure\Persistence\Repository\EloquentAlbumPhotoRepository;
use Johncms\Modules\Profile\Infrastructure\Persistence\Repository\EloquentBanRepository;
use Johncms\Modules\Profile\Infrastructure\Persistence\Repository\EloquentIpHistoryRepository;
use Johncms\Modules\Profile\Infrastructure\Persistence\Repository\EloquentKarmaRepository;
use Johncms\Modules\Profile\Infrastructure\Persistence\Repository\EloquentProfileActivityRepository;
use Johncms\Modules\Profile\Infrastructure\Persistence\Repository\ProfileUserRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();


    $services->load(
        'Johncms\\Modules\\Profile\\Application\\',
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
        'Johncms\\Modules\\Profile\\Infrastructure\\',
        dirname(__DIR__) . '/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(ProfileUserRepositoryInterface::class, ProfileUserRepository::class)->public();
    $services->set(KarmaRepositoryInterface::class, EloquentKarmaRepository::class)->public();
    $services->set(IpHistoryRepositoryInterface::class, EloquentIpHistoryRepository::class)->public();
    $services->set(AlbumPhotoRepositoryInterface::class, EloquentAlbumPhotoRepository::class)->public();
    $services->set(ProfileActivityRepositoryInterface::class, EloquentProfileActivityRepository::class)->public();
    $services->set(BanRepositoryInterface::class, EloquentBanRepository::class)->public();
};
