<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Admin\Domain\Repository\CounterRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\DashboardRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\ForumAdminRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\ForumConfigRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\ForumStructureRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\HiddenForumRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\StaffRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\AdRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\BanAmnestyRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\AuthConfigRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\AuthLogRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\BanListRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\InactiveUsersRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\IpBanRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\IpSearchRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\RegistrationModerationRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\SmiliesCacheRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\UserDeletionRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\UserListRepositoryInterface;
use Johncms\Modules\Admin\Domain\Services\FileIntegrityScannerInterface;
use Johncms\Modules\Admin\Domain\Services\LanguageCatalogInterface;
use Johncms\Modules\Admin\Domain\Services\LanguageFilesManagerInterface;
use Johncms\Modules\Admin\Domain\Services\SmiliesScannerInterface;
use Johncms\Modules\Admin\Domain\Services\ThemeListProviderInterface;
use Johncms\Modules\Admin\Domain\Services\WhoisClientInterface;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentAdRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentBanAmnestyRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentAuthLogRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentBanListRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentCounterRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentDashboardRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentForumAdminRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentForumStructureRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentHiddenForumRepository;
use Johncms\Modules\Admin\Infrastructure\Cache\FileSmiliesCacheRepository;
use Johncms\Modules\Admin\Infrastructure\Config\FileSystemAuthConfigRepository;
use Johncms\Modules\Admin\Infrastructure\Config\FileSystemConfigRepository;
use Johncms\Modules\Admin\Infrastructure\Config\FileSystemForumConfigRepository;
use Johncms\Modules\Admin\Infrastructure\Language\FileSystemLanguageFilesManager;
use Johncms\Modules\Admin\Infrastructure\Language\HttpLanguageCatalog;
use Johncms\Modules\Admin\Infrastructure\Smilies\FileSystemSmiliesScanner;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentInactiveUsersRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentIpBanRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentKarmaRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentIpSearchRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentRegistrationModerationRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentStaffRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentUserDeletionRepository;
use Johncms\Modules\Admin\Infrastructure\Security\CrcFileIntegrityScanner;
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
    $services->set(AuthLogRepositoryInterface::class, EloquentAuthLogRepository::class)->public();
    $services->set(AuthConfigRepositoryInterface::class, FileSystemAuthConfigRepository::class)->public();
    $services->set(SystemConfigRepositoryInterface::class, FileSystemConfigRepository::class)->public();
    $services->set(ThemeListProviderInterface::class, FileSystemThemeListProvider::class)->public();
    $services->set(SmiliesScannerInterface::class, FileSystemSmiliesScanner::class)->autowire()->public();
    $services->set(SmiliesCacheRepositoryInterface::class, FileSmiliesCacheRepository::class)->public();
    $services->set(LanguageFilesManagerInterface::class, FileSystemLanguageFilesManager::class)->public();
    $services->set(LanguageCatalogInterface::class, HttpLanguageCatalog::class)->public();
    $services->set(RegistrationModerationRepositoryInterface::class, EloquentRegistrationModerationRepository::class)->public();
    $services->set(InactiveUsersRepositoryInterface::class, EloquentInactiveUsersRepository::class)->public();
    $services->set(UserDeletionRepositoryInterface::class, EloquentUserDeletionRepository::class)->public();
    $services->set(BanAmnestyRepositoryInterface::class, EloquentBanAmnestyRepository::class)->public();
    $services->set(IpBanRepositoryInterface::class, EloquentIpBanRepository::class)->public();
    $services->set(KarmaRepositoryInterface::class, EloquentKarmaRepository::class)->public();
    $services->set(CounterRepositoryInterface::class, EloquentCounterRepository::class)->public();
    $services->set(AdRepositoryInterface::class, EloquentAdRepository::class)->public();
    $services->set(ForumAdminRepositoryInterface::class, EloquentForumAdminRepository::class)->public();
    $services->set(ForumConfigRepositoryInterface::class, FileSystemForumConfigRepository::class)->public();
    $services->set(ForumStructureRepositoryInterface::class, EloquentForumStructureRepository::class)->public();
    $services->set(HiddenForumRepositoryInterface::class, EloquentHiddenForumRepository::class)->public();
    $services->set(FileIntegrityScannerInterface::class, CrcFileIntegrityScanner::class)->public();
};
