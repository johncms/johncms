<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Intervention\Image\ImageManager;
use Johncms\Ads;
use Johncms\AdsFactory;
use Johncms\Cache;
use Johncms\Counters;
use Johncms\CountersFactory;
use Johncms\Database\PdoFactory;
use Johncms\Files\Filesystem;
use Johncms\Files\FileStorage;
use Johncms\ImageManagerFactory;
use Johncms\Logs\LoggerFactory;
use Johncms\Mail\MailFactory;
use Johncms\Media\MediaEmbed;
use Johncms\NavChain;
use Johncms\Router\RouteCollectorFactory;
use Johncms\Router\RequestContextFactory;
use Johncms\Router\SymfonyRouteMatcher;
use Johncms\Security\Csrf;
use Johncms\Security\HTMLPurifier;
use Johncms\Sitemap\SitemapGenerator;
use Johncms\System\Http\Environment;
use Johncms\System\Http\Request;
use Johncms\System\Http\RequestFactory;
use Johncms\System\i18n\Translator;
use Johncms\System\i18n\TranslatorServiceFactory;
use Johncms\System\Legacy\Bbcode;
use Johncms\System\Legacy\Tools;
use Johncms\System\Users\UserFactory;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Extension\Avatar;
use Johncms\System\View\Render;
use Johncms\System\View\RenderEngineFactory;
use Johncms\System\View\Theme;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Simba77\EmbedMedia\Embed;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    // Any Symfony Console Command service is auto-registered in the CLI application.
    $services->instanceof(Command::class)->tag('johncms.console_command');

    $services->load(
        'Johncms\\',
        ROOT_PATH . 'system/src'
    )
        ->exclude(
            [
                ROOT_PATH . 'system/src/Counters.php',
                ROOT_PATH . 'system/src/FileInfo.php',
                ROOT_PATH . 'system/src/Config',
                ROOT_PATH . 'system/src/Files',
                ROOT_PATH . 'system/src/Modules',
                ROOT_PATH . 'system/src/Router/Route.php',
                ROOT_PATH . 'system/src/Router/RouteCollection.php',
                ROOT_PATH . 'system/src/Router/RouteRequirements.php',
                ROOT_PATH . 'system/src/Router/RouteMatchResult.php',
                ROOT_PATH . 'system/src/Validator',
                ROOT_PATH . 'system/src/Ads.php',
                ROOT_PATH . 'system/src/Sitemap/SitemapUrlEntry.php',
                ROOT_PATH . 'system/src/Scheduler/AsScheduledTask.php',
                ROOT_PATH . 'system/src/Scheduler/ScheduledTaskDefinition.php',
                ROOT_PATH . 'system/src/Http/PageMeta.php',
                ROOT_PATH . 'system/src/Http/Pagination/Pagination.php',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(ContainerInterface::class)->synthetic();
    $services->set(Filesystem::class, Filesystem::class);
    $services->set(FileStorage::class, FileStorage::class);
    $services->set(LoggerInterface::class)->factory(service(LoggerFactory::class));
    $services->set(Request::class)->factory(service(RequestFactory::class));
    $services->set(\PDO::class, PdoFactory::class)->factory(service(PdoFactory::class));
    $services->set(\Johncms\Users\User::class)->factory(service(\Johncms\Users\UserFactory::class));
    $services->set(\Johncms\System\Users\User::class)->factory(service(UserFactory::class));

    $services->set(Bbcode::class)->factory([Bbcode::class, 'create']);
    $services->set(Tools::class)->factory([Tools::class, 'create']);
    $services->set(NavChain::class)->factory([NavChain::class, 'create']);
    $services->set(ImageManager::class)->factory(service(ImageManagerFactory::class));
    $services->set(Ads::class)->factory(service(AdsFactory::class));
    $services->set(Csrf::class)->factory([Csrf::class, 'create']);
    $services->set('counters', Counters::class)->factory(service(CountersFactory::class));
    $services->set(MailFactory::class)->factory([MailFactory::class, 'create']);
    $services->set(HTMLPurifier::class)->factory([HTMLPurifier::class, 'create']);
    $services->set(\HTMLPurifier::class)->factory([HTMLPurifier::class, 'create']);
    $services->set(\HTMLPurifier::class, \HTMLPurifier::class);

    $services->set(Assets::class)->factory([Assets::class, 'create']);
    $services->set(Avatar::class)->factory([Avatar::class, 'create']);
    $services->set(Environment::class)->factory([Environment::class, 'create']);
    $services->set(RouteCollection::class)->factory(service(RouteCollectorFactory::class));
    $services->set(RequestContext::class)->factory(service(RequestContextFactory::class));
    $services->set(UrlMatcher::class)
        ->arg('$routes', service(RouteCollection::class))
        ->arg('$context', service(RequestContext::class));
    $services->alias(UrlMatcherInterface::class, UrlMatcher::class);
    $services->set(SymfonyRouteMatcher::class);
    $services->set(Render::class)->factory(service(RenderEngineFactory::class));
    $services->set(Translator::class)->factory(service(TranslatorServiceFactory::class));
    $services->set(Cache::class)->factory([Cache::class, 'create']);
    $services->set(SitemapGenerator::class)->arg('$moduleProviders', tagged_iterator('johncms.sitemap_provider'));
    $services->set(MediaEmbed::class)->factory([MediaEmbed::class, 'create']);
    $services->set(Embed::class)->factory([MediaEmbed::class, 'create']);
    $services->set(Theme::class);
    $services->set(\Johncms\Scheduler\ScheduleMutexInterface::class, \Johncms\Scheduler\FileScheduleMutex::class);
    $services->set(\Johncms\Scheduler\ScheduledTaskRegistry::class)
        ->arg('$commands', tagged_iterator('johncms.console_command'));
    $services->set(Application::class)
        ->factory(service(\Johncms\Console\ConsoleApplicationFactory::class))
        ->arg('$commands', tagged_iterator('johncms.console_command'));
};
