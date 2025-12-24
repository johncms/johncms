<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use News\Article;
use News\Section;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->load(
        'News\\Controllers\\',
        MODULES_PATH . 'news/Controllers'
    )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(Article::class, Article::class)->public();
    $services->set(Section::class, Section::class)->public();
};
