<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Admin\Domain\Repository\DashboardRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\StaffRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\BanListRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\IpSearchRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\SmiliesCacheRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\UserListRepositoryInterface;
use Johncms\Modules\Admin\Domain\Services\SmiliesScannerInterface;
use Johncms\Modules\Admin\Domain\Services\ThemeListProviderInterface;
use Johncms\Modules\Admin\Domain\Services\WhoisClientInterface;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentBanListRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentDashboardRepository;
use Johncms\Modules\Admin\Infrastructure\Cache\FileSmiliesCacheRepository;
use Johncms\Modules\Admin\Infrastructure\Config\FileSystemConfigRepository;
use Johncms\Modules\Admin\Infrastructure\Smilies\FileSystemSmiliesScanner;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentIpSearchRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentStaffRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentUserListRepository;
use Johncms\Modules\Admin\Infrastructure\Theme\FileSystemThemeListProvider;
use Johncms\Modules\Admin\Infrastructure\Whois\SocketWhoisClient;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Admin\\Application\\',
        MODULES_PATH . 'admin/src/Application'
    )
        ->exclude(
            [
                MODULES_PATH . 'admin/src/Application/DTO',
                MODULES_PATH . 'admin/src/Application/Exceptions',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Admin\\Infrastructure\\',
        MODULES_PATH . 'admin/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(DashboardRepositoryInterface::class, EloquentDashboardRepository::class)->public();
    $services->set(StaffRepositoryInterface::class, EloquentStaffRepository::class)->public();
    $services->set(UserListRepositoryInterface::class, EloquentUserListRepository::class)->public();
    $services->set(WhoisClientInterface::class, SocketWhoisClient::class)->public();
    $services->set(IpSearchRepositoryInterface::class, EloquentIpSearchRepository::class)->public();
    $services->set(BanListRepositoryInterface::class, EloquentBanListRepository::class)->public();
    $services->set(SystemConfigRepositoryInterface::class, FileSystemConfigRepository::class)->public();
    $services->set(ThemeListProviderInterface::class, FileSystemThemeListProvider::class)->public();
    $services->set(SmiliesScannerInterface::class, FileSystemSmiliesScanner::class)->autowire()->public();
    $services->set(SmiliesCacheRepositoryInterface::class, FileSmiliesCacheRepository::class)->public();
};
