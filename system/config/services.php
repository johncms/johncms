<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Container\PSRContainerFactory;
use Johncms\Files\FileStorage;
use Johncms\Logs\LoggerFactory;
use Johncms\System\Database\PdoFactory;
use Johncms\System\Http\Request;
use Johncms\System\Http\RequestFactory;
use Johncms\System\Users\UserFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\',
        ROOT_PATH . 'system/src'
    )
        ->exclude(
            [
                ROOT_PATH . 'system/src/Counters.php',
                ROOT_PATH . 'system/src/FileInfo.php',
                ROOT_PATH . 'system/src/Files',
                ROOT_PATH . 'system/src/Modules',
                ROOT_PATH . 'system/src/Validator',
                ROOT_PATH . 'system/src/Ads.php',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(FileStorage::class, FileStorage::class);
    $services->set(LoggerInterface::class)->factory(service(LoggerFactory::class));
    $services->set(ContainerInterface::class)->factory(service(PSRContainerFactory::class));
    $services->set(Request::class)->factory(service(RequestFactory::class));
    $services->set(\PDO::class, PdoFactory::class)->factory(service(PdoFactory::class));
    $services->set(\Johncms\Users\User::class)->factory(service(UserFactory::class))->lazy();
    $services->set(\Johncms\System\Users\User::class)->factory(service(\Johncms\Users\UserFactory::class));
};
