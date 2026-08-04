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
use Johncms\Security\AntifloodChecker;
use Johncms\Security\FileRequestRateLog;
use Johncms\Security\RequestRateLogInterface;
use Johncms\Security\AntifloodCheckerInterface;
use Johncms\Security\HTMLPurifier;
use Johncms\Smilies\SmiliesRenderer;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Utils\DateFormatter;
use Johncms\Utils\DateFormatterInterface;
use Johncms\Users\IgnoreListChecker;
use Johncms\Users\IgnoreListCheckerInterface;
use Johncms\Users\UserPlaceFormatter;
use Johncms\Users\UserPlaceFormatterInterface;
use Johncms\Sitemap\SitemapGenerator;
use Johncms\Http\Environment;
use Johncms\Http\Session;
use Johncms\Http\SessionFactory;
use Johncms\System\i18n\Translator;
use Johncms\System\i18n\TranslatorServiceFactory;
use Johncms\System\Users\UserFactory;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Extension\Avatar;
use Johncms\System\View\Extension\Formatter;
use Johncms\System\View\Extension\Vite;
use Johncms\System\View\Render;
use Johncms\System\View\RenderEngineFactory;
use Johncms\View\ColorScheme;
use Johncms\View\DelegatingRenderer;
use Johncms\View\PlatesRenderer;
use Johncms\View\RendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Simba77\EmbedMedia\Embed;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Component\Routing\RouteCollection;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    // Any Symfony Console Command service is auto-registered in the CLI application.
    $services->instanceof(Command::class)->tag('johncms.console_command');

    // Defined before the ResetInterface rule below on purpose: instanceof conditionals apply to
    // the definitions that follow them, and the console application implements ResetInterface.
    // Tagged as resettable it would have the kernel build the whole console application, every
    // command included, on every HTTP request just to reset it — while an HTTP cycle never
    // touches it and it holds nothing belonging to a request.
    $services->set(Application::class)
        ->factory(service(\Johncms\Console\ConsoleApplicationFactory::class))
        ->arg('$commands', tagged_iterator('johncms.console_command'));

    // A shared service that caches something belonging to one request implements ResetInterface;
    // the kernel clears every one of them before it starts serving the next request.
    $services->instanceof(ResetInterface::class)->tag('johncms.resettable');

    $services->load(
        'Johncms\\',
        ROOT_PATH . 'system/src'
    )
        ->exclude(
            [
                ROOT_PATH . 'system/src/Counters.php',
                ROOT_PATH . 'system/src/FileInfo.php',
                ROOT_PATH . 'system/src/Config',
                // Exceptions are never services: those with scalar constructor arguments
                // (HttpRedirectException) break the container compilation when autowired.
                ROOT_PATH . 'system/src/Exceptions',
                ROOT_PATH . 'system/src/Files',
                ROOT_PATH . 'system/src/Modules',
                ROOT_PATH . 'system/src/Router/Route.php',
                ROOT_PATH . 'system/src/Router/RouteCollection.php',
                ROOT_PATH . 'system/src/Router/RouteRequirements.php',
                ROOT_PATH . 'system/src/Router/RouteMatchResult.php',
                ROOT_PATH . 'system/src/Validator',
                ROOT_PATH . 'system/src/Ads.php',
                ROOT_PATH . 'system/src/Sitemap/SitemapUrlEntry.php',
                ROOT_PATH . 'system/src/AdminTasks/AsAdminTask.php',
                ROOT_PATH . 'system/src/AdminTasks/AdminTaskDefinition.php',
                ROOT_PATH . 'system/src/AdminTasks/AdminTaskState.php',
                ROOT_PATH . 'system/src/AdminTasks/AdminTaskStatus.php',
                ROOT_PATH . 'system/src/AdminTasks/AdminTaskBusyException.php',
                ROOT_PATH . 'system/src/Scheduler/AsScheduledTask.php',
                ROOT_PATH . 'system/src/Scheduler/ScheduledTaskDefinition.php',
                ROOT_PATH . 'system/src/Http/PageMeta.php',
                // The request is not a service: it belongs to a cycle, and a container-built one
                // would be an empty request assembled from the globals of whoever asked first.
                ROOT_PATH . 'system/src/Http/Request.php',
                ROOT_PATH . 'system/src/Http/Pagination/Pagination.php',
                ROOT_PATH . 'system/src/Http/UploadedFileDTO.php',
                ROOT_PATH . 'system/src/Security/ClientInfoDTO.php',
            ]
        )
        ->autowire()
        ->autoconfigure()
        ->public();

    // The container itself, published by PSRContainerFactory. Only the PSR interface is exposed:
    // nothing in the application needs the Symfony-specific part of the contract.
    $services->set(ContainerInterface::class)->synthetic();
    $services->set(Filesystem::class, Filesystem::class);
    $services->set(FileStorage::class, FileStorage::class);
    $services->set(LoggerInterface::class)->factory(service(LoggerFactory::class));
    // The request is not a service: a controller takes it as an action argument, and a service
    // that outlives a single request reads the current one off the stack below. The kernel pushes
    // the request of every cycle onto it, so a singleton holding the stack always reads the
    // request being served rather than the one it was built with.
    $services->set(RequestStack::class, RequestStack::class);
    $services->set(\Johncms\Http\Kernel::class)
        ->arg('$resettableServices', tagged_iterator('johncms.resettable'));
    // The session storage depends on the runtime (native under HTTP, in-memory in the console),
    // so the facade is built by a factory instead of being autowired from its constructor.
    $services->set(Session::class)->factory(service(SessionFactory::class));
    $services->set(\PDO::class, PdoFactory::class)->factory(service(PdoFactory::class));
    $services->set(\Johncms\Users\User::class)->factory(service(\Johncms\Users\UserFactory::class));
    $services->set(\Johncms\Users\Repository\UserRepositoryInterface::class, \Johncms\Users\Repository\EloquentUserRepository::class);
    $services->set(\Johncms\System\Users\User::class)->factory(service(UserFactory::class));

    $services->set(AntifloodCheckerInterface::class, AntifloodChecker::class)->autowire();
    $services->set(RequestRateLogInterface::class, FileRequestRateLog::class);
    $services->set(SmiliesRendererInterface::class, SmiliesRenderer::class);
    $services->set(UserPlaceFormatterInterface::class, UserPlaceFormatter::class)->autowire();
    $services->set(IgnoreListCheckerInterface::class, IgnoreListChecker::class);
    $services->set(DateFormatterInterface::class, DateFormatter::class)->autowire();
    $services->set(NavChain::class)->factory([NavChain::class, 'create']);
    $services->set(ImageManager::class)->factory(service(ImageManagerFactory::class));
    $services->set(Ads::class)->factory(service(AdsFactory::class));
    $services->set(Csrf::class)->factory([Csrf::class, 'create']);
    $services->set('counters', Counters::class)->factory(service(CountersFactory::class));
    $services->set(MailFactory::class)->factory([MailFactory::class, 'create']);
    $services->set(HTMLPurifier::class)->factory([HTMLPurifier::class, 'create']);
    $services->set(\HTMLPurifier::class, \HTMLPurifier::class)->factory([HTMLPurifier::class, 'create']);

    $services->set(Assets::class)->factory([Assets::class, 'create']);
    $services->set(Avatar::class)->factory([Avatar::class, 'create']);
    $services->set(Vite::class);
    $services->set(Formatter::class)->autowire();
    $services->set(Environment::class)->autowire();
    $services->set(RouteCollection::class)->factory(service(RouteCollectorFactory::class));
    $services->set(RequestContext::class)->factory(service(RequestContextFactory::class));
    $services->set(UrlMatcher::class)
        ->arg('$routes', service(RouteCollection::class))
        ->arg('$context', service(RequestContext::class));
    $services->alias(UrlMatcherInterface::class, UrlMatcher::class);
    $services->set(SymfonyRouteMatcher::class);
    $services->set(Render::class)->factory(service(RenderEngineFactory::class));
    // Templates are dispatched by the shape of their name — @namespace/file.twig to Twig,
    // namespace::file to Plates — so a page moves to Twig on its own, without its module or
    // any configuration moving with it. The Twig renderer joins the constructor once it exists.
    $services->set(PlatesRenderer::class)->arg('$engine', service(Render::class));
    $services->set(RendererInterface::class, DelegatingRenderer::class)
        ->arg('$platesRenderer', service(PlatesRenderer::class))
        ->arg('$twigRenderer', null);
    $services->set(Translator::class)->factory(service(TranslatorServiceFactory::class));
    $services->set(Cache::class)->factory([Cache::class, 'create']);
    $services->set(SitemapGenerator::class)->arg('$moduleProviders', tagged_iterator('johncms.sitemap_provider'));
    $services->set(MediaEmbed::class)->factory([MediaEmbed::class, 'create']);
    $services->set(Embed::class)->factory([MediaEmbed::class, 'create']);
    $services->set(ColorScheme::class);
    $services->set(\Johncms\Scheduler\ScheduleMutexInterface::class, \Johncms\Scheduler\FileScheduleMutex::class);
    $services->set(\Johncms\Scheduler\ScheduledTaskRegistry::class)
        ->arg('$commands', tagged_iterator('johncms.console_command'));
    $services->set(\Johncms\AdminTasks\AdminTaskRegistry::class)
        ->arg('$commands', tagged_iterator('johncms.console_command'));
    $services->set(\Johncms\AdminTasks\AdminTaskRunner::class)
        ->arg('$mutex', service(\Johncms\AdminTasks\FileAdminTaskMutex::class));
};
