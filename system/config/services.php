<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Illuminate\Contracts\Cache\Repository as CacheRepositoryInterface;
use Intervention\Image\ImageManager;
use Johncms\Ads;
use Johncms\AdsFactory;
use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authentication\AuthenticatorInterface;
use Johncms\Auth\Authorization\AccessChecker;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\AccessVoterInterface;
use Johncms\Auth\Authorization\PermissionProviderInterface;
use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentAuthSessionRepository;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentPasswordResetTokenRepository;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentRoleRepository;
use Johncms\Auth\Password\PasswordHasherFactory;
use Johncms\Auth\Password\PasswordHasherInterface;
use Johncms\Auth\Password\PasswordResetTokenRepositoryInterface;
use Johncms\Auth\Session\AuthSessionRepositoryInterface;
use Johncms\Auth\Session\SessionSettings;
use Johncms\Auth\Session\SessionSettingsFactory;
use Johncms\Auth\Throttling\CacheLoginThrottle;
use Johncms\Auth\Throttling\LoginThrottleInterface;
use Johncms\Cache;
use Johncms\Counters;
use Johncms\CountersFactory;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Johncms\Database\PdoFactory;
use Johncms\Database\SchemaBuilderFactory;
use Johncms\Files\Filesystem;
use Johncms\Files\FileStorage;
use Johncms\ImageManagerFactory;
use Johncms\Logs\LoggerFactory;
use Johncms\Mail\MailFactory;
use Johncms\Media\MediaEmbed;
use Johncms\NavChain;
use Johncms\Router\RouteCollectorFactory;
use Johncms\Router\RequestContextFactory;
use Johncms\Router\UrlMatcherFactory;
use Johncms\Router\SymfonyRouteMatcher;
use Johncms\Security\Csrf;
use Johncms\Security\CsrfExemptions;
use Johncms\Security\AntifloodChecker;
use Johncms\Security\FileRequestRateLog;
use Johncms\Security\RequestRateLogInterface;
use Johncms\Security\AntifloodCheckerInterface;
use Johncms\Security\HTMLPurifier;
use Johncms\Smilies\SmiliesRenderer;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Utils\DateFormatter;
use Johncms\Validator\RuleCompiler;
use Johncms\Validator\RuleConstraintFactoryInterface;
use Johncms\Validator\SymfonyValidator;
use Johncms\Validator\SymfonyValidatorFactory;
use Johncms\Validator\ValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorEngine;
use Johncms\Utils\DateFormatterInterface;
use Johncms\Users\IgnoreListChecker;
use Johncms\Users\IgnoreListCheckerInterface;
use Johncms\Users\UserPlaceFormatter;
use Johncms\Users\UserPlaceFormatterInterface;
use Johncms\Sitemap\SitemapGenerator;
use Johncms\Http\Environment;
use Johncms\Http\ResponseNormalizer;
use Johncms\Http\Session;
use Johncms\Http\SessionFactory;
use Johncms\System\i18n\Translator;
use Johncms\System\i18n\TranslatorServiceFactory;
use Johncms\Console\Commands\I18nScanCommand;
use Johncms\Console\Commands\TwigCompileCommand;
use Johncms\Console\Commands\TwigLintCommand;
use Johncms\View\ColorScheme;
use Johncms\View\RendererInterface;
use Johncms\View\Theme\FilesystemThemeRepository;
use Johncms\View\Theme\ThemeRepositoryInterface;
use Johncms\View\Twig\AppVariable;
use Johncms\View\Twig\Extension\AppExtension;
use Johncms\View\Twig\Extension\AssetExtension;
use Johncms\View\Twig\Extension\AuthExtension;
use Johncms\View\Twig\Extension\FormatExtension;
use Johncms\View\Twig\Extension\MailExtension;
use Johncms\View\Twig\Extension\I18nExtension;
use Johncms\View\Twig\Extension\SiteExtension;
use Johncms\View\Twig\TemplatePathRegistry;
use Johncms\View\Twig\TwigEnvironmentFactory;
use Johncms\View\Twig\TwigRenderer;
use Johncms\View\ViewEnvironment;
use Twig\Environment as TwigEnvironment;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Simba77\EmbedMedia\Embed;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
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

    // A module adds a validation rule of its own by registering a factory with this tag — the
    // core is not touched, the way the addRule() of the previous validator allowed. Declared
    // before the directory load below, since an instanceof rule only applies to what follows it.
    $services->instanceof(RuleConstraintFactoryInterface::class)->tag('johncms.validator.rule_factory');

    // The authentication and authorization extension points are tagged by PSRContainerFactory
    // instead: an instanceof rule here would reach the services of this file only, and a module
    // declaring a voter or a permission would be silently ignored.

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
                // The rules are value objects carrying scalar constructor arguments: autowiring
                // them breaks the compilation of the whole container.
                ROOT_PATH . 'system/src/Validator/Rules',
                ROOT_PATH . 'system/src/Validator/ValidationResult.php',
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
                ROOT_PATH . 'system/src/Http/View/ViewResponse.php',
                // The request is not a service: it belongs to a cycle, and a container-built one
                // would be an empty request assembled from the globals of whoever asked first.
                ROOT_PATH . 'system/src/Http/Request.php',
                ROOT_PATH . 'system/src/Http/Pagination/Pagination.php',
                ROOT_PATH . 'system/src/Http/UploadedFileDTO.php',
                ROOT_PATH . 'system/src/Security/ClientInfoDTO.php',
                // Value objects and enums of the auth layer: Identity carries scalars, and the
                // matcher is a pure function. Autowiring them breaks the container build.
                ROOT_PATH . 'system/src/Auth/Identity.php',
                ROOT_PATH . 'system/src/Auth/AuthMethod.php',
                ROOT_PATH . 'system/src/Auth/Authentication/LoginCredentialsDTO.php',
                ROOT_PATH . 'system/src/Auth/Authentication/LoginResultDTO.php',
                ROOT_PATH . 'system/src/Auth/Authentication/LoginStatus.php',
                ROOT_PATH . 'system/src/Auth/Authorization/PermissionDefinition.php',
                ROOT_PATH . 'system/src/Auth/Authorization/PermissionMatcher.php',
                ROOT_PATH . 'system/src/Auth/Authorization/Vote.php',
                ROOT_PATH . 'system/src/Auth/Authorization/SystemRole.php',
                ROOT_PATH . 'system/src/Auth/Authorization/LegacyRightsMigrationReport.php',
                ROOT_PATH . 'system/src/Auth/SecureToken.php',
                ROOT_PATH . 'system/src/Auth/Schema',
                ROOT_PATH . 'system/src/Auth/Session/IssuedSession.php',
                ROOT_PATH . 'system/src/Auth/Session/SessionRevocationReason.php',
                // Built by SessionSettingsFactory from config, not autowired from its scalars.
                ROOT_PATH . 'system/src/Auth/Session/SessionSettings.php',
                ROOT_PATH . 'system/src/View/Theme/ThemeDTO.php',
                // Built by the scan command with the translation set it fills, not by the container.
                ROOT_PATH . 'system/src/System/i18n/TwigScanner.php',
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
    // Creating and altering tables: a handful of console commands and the installer.
    $services->set(SchemaBuilder::class)->factory(service(SchemaBuilderFactory::class));
    $services->set(\Johncms\Users\User::class)->factory(service(\Johncms\Users\UserFactory::class));
    $services->set(\Johncms\Users\Repository\UserRepositoryInterface::class, \Johncms\Users\Repository\EloquentUserRepository::class);

    // The authenticators are asked in the order they are tagged, and the order is a decision:
    // a request carrying both a bearer token and a session cookie must be answered by the token.
    $services->set(AuthenticatorChain::class)
        ->arg('$authenticators', tagged_iterator('johncms.auth.authenticator'));
    $services->set(AccessChecker::class)
        ->arg('$voters', tagged_iterator('johncms.auth.voter'));
    $services->alias(AccessCheckerInterface::class, AccessChecker::class);
    $services->set(PermissionRegistry::class)
        ->arg('$providers', tagged_iterator('johncms.auth.permissions'))
        ->arg('$definitions', []);
    $services->set(PasswordResetTokenRepositoryInterface::class, EloquentPasswordResetTokenRepository::class);
    // Reads the algorithm from config at instantiation: baking it into the compiled container
    // would keep a changed configuration from ever taking effect.
    $services->set(PasswordHasherInterface::class)->factory(service(PasswordHasherFactory::class));
    $services->set(LoginThrottleInterface::class, CacheLoginThrottle::class);
    $services->set(AuthSessionRepositoryInterface::class, EloquentAuthSessionRepository::class);
    $services->set(RoleRepositoryInterface::class, EloquentRoleRepository::class);
    // Read from config at instantiation rather than while the container is built: the built
    // container is cached, and anything resolved there would freeze the configuration into it.
    $services->set(SessionSettings::class)->factory(service(SessionSettingsFactory::class));

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
    // Reads config/csrf.php: an array argument the container cannot autowire.
    $services->set(CsrfExemptions::class)->factory([CsrfExemptions::class, 'create']);
    $services->set('counters', Counters::class)->factory(service(CountersFactory::class));
    // The counters are built by a factory under a string id; the alias is what lets a service or
    // a controller ask for them by type.
    $services->alias(Counters::class, 'counters');
    $services->set(MailFactory::class)->factory([MailFactory::class, 'create']);
    $services->set(HTMLPurifier::class)->factory([HTMLPurifier::class, 'create']);
    $services->set(\HTMLPurifier::class, \HTMLPurifier::class)->factory([HTMLPurifier::class, 'create']);

    $services->set(RuleCompiler::class)
        ->arg('$factories', tagged_iterator('johncms.validator.rule_factory'));
    // The engine itself, registered under the Symfony interface so SymfonyValidator is autowired
    // with it; the application only ever asks for our own ValidatorInterface.
    $services->set(SymfonyValidatorEngine::class)->factory(service(SymfonyValidatorFactory::class));
    $services->alias(ValidatorInterface::class, SymfonyValidator::class);

    $services->set(Environment::class)->autowire();
    $services->set(RouteCollection::class)->factory(service(RouteCollectorFactory::class));
    $services->set(RequestContext::class)->factory(service(RequestContextFactory::class));
    // The collection is passed as a closure rather than as a service: with the cache on, the
    // matcher is built from the dump and the collection is never constructed at all.
    $services->set(UrlMatcherInterface::class)
        ->factory([service(UrlMatcherFactory::class), 'create'])
        ->args([service_closure(RouteCollection::class), service(RequestContext::class), CACHE_ROUTES]);
    $services->set(SymfonyRouteMatcher::class);
    // Every page of the site is a Twig template now; the installer builds an engine of its own.
    $services->alias(RendererInterface::class, TwigRenderer::class);

    $services->set(ThemeRepositoryInterface::class, FilesystemThemeRepository::class);
    $services->set(TemplatePathRegistry::class)
        ->arg('$providers', tagged_iterator('johncms.template_paths'));
    $services->set(\Johncms\View\Twig\InstallTemplatePaths::class)->tag('johncms.template_paths');
    // One environment serves the whole of HTTP: the admin panel and the public site differ by
    // their namespaces, not by what a template can do, so they share the cache and the compiled
    // components. Mail has its own, because a message has no request behind it; the installer
    // gets one once it moves.
    $services->set('johncms.twig.web', TwigEnvironment::class)
        ->factory([service(TwigEnvironmentFactory::class), 'create'])
        ->arg('$environment', ViewEnvironment::Web)
        ->arg('$extensions', tagged_iterator('johncms.twig_extension'));
    $services->set(TwigRenderer::class)->arg('$twig', service('johncms.twig.web'));
    $services->set(TwigLintCommand::class)
        ->arg('$twig', service('johncms.twig.web'))
        ->arg('$mailTwig', service('johncms.twig.mail'));
    $services->set(I18nScanCommand::class)
        ->arg('$twig', service('johncms.twig.web'))
        ->arg('$mailTwig', service('johncms.twig.mail'));
    $services->set(TwigCompileCommand::class)
        ->arg('$twig', service('johncms.twig.web'))
        ->arg('$mailTwig', service('johncms.twig.mail'));

    // The mail environment: no request, so no visitor, no csrf token and no build assets; the
    // addresses it prints are absolute, since a message is read outside the site.
    $services->set('johncms.twig.mail', TwigEnvironment::class)
        ->factory([service(TwigEnvironmentFactory::class), 'create'])
        ->arg('$environment', ViewEnvironment::Mail)
        ->arg('$extensions', tagged_iterator('johncms.twig_extension.mail'));
    $services->set(MailExtension::class)->tag('johncms.twig_extension.mail');

    // The installer environment: it runs before there is a site, so it has no request and no
    // modules either — only the theme it ships with.
    $services->set('johncms.twig.install', TwigEnvironment::class)
        ->factory([service(TwigEnvironmentFactory::class), 'create'])
        ->arg('$environment', ViewEnvironment::Install)
        ->arg('$extensions', tagged_iterator('johncms.twig_extension.install'));
    $services->set(\Johncms\Mail\MailRenderer::class)->arg('$twig', service('johncms.twig.mail'));

    $services->set(AppVariable::class)
        ->arg('$environment', ViewEnvironment::Web)
        ->arg('$currentUser', service(\Johncms\Auth\CurrentUser::class))
        ->arg('$csrf', service_closure(Csrf::class));
    $services->set(AppExtension::class)
        ->arg('$app', service_closure(AppVariable::class))
        ->tag('johncms.twig_extension');
    $services->set(I18nExtension::class)
        ->tag('johncms.twig_extension')
        ->tag('johncms.twig_extension.mail')
        ->tag('johncms.twig_extension.install');
    $services->set(AssetExtension::class)
        ->tag('johncms.twig_extension')
        ->tag('johncms.twig_extension.install');
    $services->set(FormatExtension::class)
        ->tag('johncms.twig_extension')
        ->tag('johncms.twig_extension.mail');
    $services->set(SiteExtension::class)->tag('johncms.twig_extension');
    $services->set(AuthExtension::class)->tag('johncms.twig_extension');
    // The renderer is handed over as a closure: a controller that returns a string or a Response
    // of its own must not have the template environment assembled behind it.
    $services->set(ResponseNormalizer::class)->arg('$renderer', service_closure(RendererInterface::class));
    $services->set(Translator::class)->factory(service(TranslatorServiceFactory::class));
    $services->set(Cache::class)->factory([Cache::class, 'create']);
    // So that anything asking for a cache by the framework interface gets the application one.
    $services->alias(CacheRepositoryInterface::class, Cache::class);
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
