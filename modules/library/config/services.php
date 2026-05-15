<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Library\Domain\Repository\LibraryTextRepositoryInterface;
use Johncms\Modules\Library\Infrastructure\Persistence\Repository\LibraryTextRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Library\\Application\\',
        MODULES_PATH . 'library/src/Application'
    )
        ->exclude([MODULES_PATH . 'library/src/Application/Services'])
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Library\\Infrastructure\\',
        MODULES_PATH . 'library/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(LibraryTextRepositoryInterface::class, LibraryTextRepository::class)->public();
};
