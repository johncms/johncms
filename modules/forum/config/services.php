<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumSearchHistoryRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumSearchRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumUnreadRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumWhoRepositoryInterface;
use Johncms\Modules\Forum\Application\Sitemap\ForumUrlsProvider;
use Johncms\Modules\Forum\Infrastructure\Persistence\Repository\ForumFileRepository;
use Johncms\Modules\Forum\Infrastructure\Persistence\Repository\ForumMessageFileRepository;
use Johncms\Modules\Forum\Infrastructure\Persistence\Repository\ForumMessageRepository;
use Johncms\Modules\Forum\Infrastructure\Persistence\Repository\ForumSectionRepository;
use Johncms\Modules\Forum\Infrastructure\Persistence\Repository\ForumSearchHistoryRepository;
use Johncms\Modules\Forum\Infrastructure\Persistence\Repository\ForumSearchRepository;
use Johncms\Modules\Forum\Infrastructure\Persistence\Repository\ForumTopicRepository;
use Johncms\Modules\Forum\Infrastructure\Persistence\Repository\ForumUnreadRepository;
use Johncms\Modules\Forum\Infrastructure\Persistence\Repository\ForumVoteRepository;
use Johncms\Modules\Forum\Infrastructure\Persistence\Repository\ForumWhoRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->instanceof(\Symfony\Component\Console\Command\Command::class)->tag('johncms.console_command');

    $services->load(
        'Johncms\\Modules\\Forum\\Application\\',
        MODULES_PATH . 'forum/src/Application'
    )
        ->exclude(
            [
                MODULES_PATH . 'forum/src/Application/DTO',
                MODULES_PATH . 'forum/src/Application/Exceptions',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load(
        'Johncms\\Modules\\Forum\\Infrastructure\\',
        MODULES_PATH . 'forum/src/Infrastructure'
    )
        ->autowire()
        ->autoconfigure();

    $services->set(ForumMessageRepositoryInterface::class, ForumMessageRepository::class)->public();
    $services->set(ForumMessageFileRepositoryInterface::class, ForumMessageFileRepository::class)->public();
    $services->set(ForumFileRepositoryInterface::class, ForumFileRepository::class)->public();
    $services->set(ForumSectionRepositoryInterface::class, ForumSectionRepository::class)->public();
    $services->set(ForumSearchRepositoryInterface::class, ForumSearchRepository::class)->autowire()->public();
    $services->set(ForumSearchHistoryRepositoryInterface::class, ForumSearchHistoryRepository::class)->autowire()->public();
    $services->set(ForumTopicRepositoryInterface::class, ForumTopicRepository::class)->public();
    $services->set(ForumUnreadRepositoryInterface::class, ForumUnreadRepository::class)->public();
    $services->set(ForumVoteRepositoryInterface::class, ForumVoteRepository::class)->public();
    $services->set(ForumWhoRepositoryInterface::class, ForumWhoRepository::class)->public();
    $services->set(ForumUrlsProvider::class, ForumUrlsProvider::class)->autowire()->tag('johncms.sitemap_provider')->public();
};
