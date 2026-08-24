<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Downloads\Application\Sitemap\DownloadsUrlsProvider;
use Johncms\Modules\Downloads\Domain\Repository\DownloadCategoryRepositoryInterface;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Johncms\Modules\Downloads\Infrastructure\Persistence\Repository\DownloadCategoryRepository;
use Johncms\Modules\Downloads\Infrastructure\Persistence\Repository\DownloadFileRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Any Symfony Console Command service is auto-registered in the CLI application.
    $services->instanceof(\Symfony\Component\Console\Command\Command::class)->tag('johncms.console_command');

    $services->load(
        'Johncms\\Modules\\Downloads\\Application\\',
        MODULES_PATH . 'johncms/downloads/src/Application'
    )
        ->exclude(
            [
                MODULES_PATH . 'johncms/downloads/src/Application/DTO',
                MODULES_PATH . 'johncms/downloads/src/Application/Exceptions',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Downloads\\Infrastructure\\',
        MODULES_PATH . 'johncms/downloads/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(DownloadFileRepositoryInterface::class, DownloadFileRepository::class)->public();
    $services->set(DownloadCategoryRepositoryInterface::class, DownloadCategoryRepository::class)->public();

    $services->set(DownloadsUrlsProvider::class, DownloadsUrlsProvider::class)->tag('johncms.sitemap_provider')->public();
};
