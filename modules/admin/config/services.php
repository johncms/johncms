<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Admin\Domain\Repository\DashboardRepositoryInterface;
use Johncms\Modules\Admin\Domain\Repository\StaffRepositoryInterface;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentDashboardRepository;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentStaffRepository;

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
};
