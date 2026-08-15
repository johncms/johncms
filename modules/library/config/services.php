<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Library\Application\Services\LibraryArticlePathService;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Modules\Library\Application\Services\LibrarySlugService;
use Johncms\Modules\Library\Application\Sitemap\LibraryUrlsProvider;
use Johncms\Modules\Library\Domain\Repository\LibraryTextRepositoryInterface;
use Johncms\Modules\Library\Infrastructure\Persistence\Repository\LibraryTextRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Any Symfony Console Command service is auto-registered in the CLI application.
    $services->instanceof(\Symfony\Component\Console\Command\Command::class)->tag('johncms.console_command');

    $services->load(
        'Johncms\\Modules\\Library\\Application\\',
        MODULES_PATH . 'library/src/Application'
    )
        ->exclude([MODULES_PATH . 'library/src/Application/Services'])
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(LibraryCategoryPathService::class)->autowire()->public();
    $services->set(LibraryArticlePathService::class)->autowire()->public();
    $services->set(LibrarySlugService::class)->autowire()->public();
    // The Services directory is not loaded as a whole, so the provider of the permissions is
    // registered by hand; autoconfigure() is what puts the tag of the extension point on it.
    $services->set(LibraryPermissions::class)->autowire()->autoconfigure();

    $services->set(LibraryUrlsProvider::class, LibraryUrlsProvider::class)->tag('johncms.sitemap_provider')->public();

    $services->load(
        'Johncms\\Modules\\Library\\Infrastructure\\',
        MODULES_PATH . 'library/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(LibraryTextRepositoryInterface::class, LibraryTextRepository::class)->public();
};
