<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Downloads\Domain\Repository\DownloadCategoryRepositoryInterface;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Johncms\Modules\Downloads\Infrastructure\Persistence\Repository\DownloadCategoryRepository;
use Johncms\Modules\Downloads\Infrastructure\Persistence\Repository\DownloadFileRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Downloads\\Application\\',
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
        'Johncms\\Modules\\Downloads\\Infrastructure\\',
        dirname(__DIR__) . '/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(DownloadFileRepositoryInterface::class, DownloadFileRepository::class)->public();
    $services->set(DownloadCategoryRepositoryInterface::class, DownloadCategoryRepository::class)->public();
};
