<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Admin\Domain\Repository\CounterRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\DashboardRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\AdRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\BanAmnestyRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\AuthConfigRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\AuthLogRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\BanListRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\InactiveUsersRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\IpSearchRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\RegistrationModerationRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\CaptchaConfigRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\MailConfigRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\UserDeletionRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\UserCardRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\UserEditRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\UserListRepositoryInterface;
use Johncms\Modules\Admin\Domain\Services\LanguageFilesManagerInterface;
use Johncms\Modules\Admin\Domain\Services\ThemeListProviderInterface;
use Johncms\Modules\Admin\Domain\Services\IpRegistryInterface;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentAdRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentBanAmnestyRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentAuthLogRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentBanListRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentCounterRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentDashboardRepository;
use Johncms\Modules\Admin\Infrastructure\Config\FileSystemAuthConfigRepository;
use Johncms\Modules\Admin\Infrastructure\Config\FileCaptchaConfigRepository;
use Johncms\Modules\Admin\Infrastructure\Config\FileMailConfigRepository;
use Johncms\Modules\Admin\Infrastructure\Config\FileSystemConfigRepository;
use Johncms\Modules\Admin\Infrastructure\Language\FileSystemLanguageFilesManager;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentInactiveUsersRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentKarmaRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentIpSearchRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentRegistrationModerationRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentUserDeletionRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentUserCardRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentUserEditRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentUserListRepository;
use Johncms\Modules\Admin\Infrastructure\Theme\FileSystemThemeListProvider;
use Johncms\Modules\Admin\Infrastructure\Rdap\RdapIpRegistry;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Admin\\Application\\',
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
        'Johncms\\Modules\\Admin\\Infrastructure\\',
        dirname(__DIR__) . '/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(DashboardRepositoryInterface::class, EloquentDashboardRepository::class)->public();
    $services->set(UserListRepositoryInterface::class, EloquentUserListRepository::class)->public();
    $services->set(UserCardRepositoryInterface::class, EloquentUserCardRepository::class)->public();
    $services->set(UserEditRepositoryInterface::class, EloquentUserEditRepository::class)->public();
    $services->set(IpRegistryInterface::class, RdapIpRegistry::class)->autowire()->public();
    $services->set(IpSearchRepositoryInterface::class, EloquentIpSearchRepository::class)->public();
    $services->set(BanListRepositoryInterface::class, EloquentBanListRepository::class)->public();
    $services->set(AuthLogRepositoryInterface::class, EloquentAuthLogRepository::class)->public();
    $services->set(AuthConfigRepositoryInterface::class, FileSystemAuthConfigRepository::class)->autowire()->public();
    $services->set(SystemConfigRepositoryInterface::class, FileSystemConfigRepository::class)->public();
    $services->set(MailConfigRepositoryInterface::class, FileMailConfigRepository::class)->public();
    $services->set(CaptchaConfigRepositoryInterface::class, FileCaptchaConfigRepository::class)->autowire()->public();
    $services->set(ThemeListProviderInterface::class, FileSystemThemeListProvider::class)->public();
    $services->set(LanguageFilesManagerInterface::class, FileSystemLanguageFilesManager::class)->public();
    $services->set(RegistrationModerationRepositoryInterface::class, EloquentRegistrationModerationRepository::class)->public();
    $services->set(InactiveUsersRepositoryInterface::class, EloquentInactiveUsersRepository::class)->public();
    $services->set(UserDeletionRepositoryInterface::class, EloquentUserDeletionRepository::class)->public();
    $services->set(BanAmnestyRepositoryInterface::class, EloquentBanAmnestyRepository::class)->public();
    $services->set(KarmaRepositoryInterface::class, EloquentKarmaRepository::class)->public();
    $services->set(CounterRepositoryInterface::class, EloquentCounterRepository::class)->public();
    $services->set(AdRepositoryInterface::class, EloquentAdRepository::class)->public();
};
