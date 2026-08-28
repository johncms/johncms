<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Consent\Application\View\CookieBannerExtension;
use Johncms\Modules\Consent\Domain\Repository\ConsentLogRepositoryInterface;
use Johncms\Modules\Consent\Domain\Repository\ConsentRepositoryInterface;
use Johncms\Modules\Consent\Infrastructure\Persistence\Repository\ConsentLogRepository;
use Johncms\Modules\Consent\Infrastructure\Persistence\Repository\ConsentRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'Johncms\\Modules\\Consent\\Application\\',
        dirname(__DIR__) . '/src/Application'
    )
        ->exclude(
            [
                dirname(__DIR__) . '/src/Application/DTO',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Consent\\Infrastructure\\',
        dirname(__DIR__) . '/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(CookieBannerExtension::class)->tag('johncms.twig_extension');

    $services->set(ConsentRepositoryInterface::class, ConsentRepository::class)->public();
    $services->set(ConsentLogRepositoryInterface::class, ConsentLogRepository::class)->public();
};
