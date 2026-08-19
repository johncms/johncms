<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Johncms\Ads;
use Johncms\Content\ContentRenderer;
use Johncms\Content\ContentRendererInterface;
use Johncms\Content\Embed\EmbedProviderRegistry;
use Johncms\Content\Transformer\ContentTransformerRegistry;
use Johncms\AdsFactory;
use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authentication\AuthenticatorInterface;
use Johncms\Auth\Authorization\AccessChecker;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\AccessVoterInterface;
use Johncms\Auth\Authorization\PermissionProviderInterface;
use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Auth\Events\AuthEventLogger;
use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\Events\AuthEventRepositoryInterface;
use Johncms\Auth\External\ExternalIdentityProviderRegistry;
use Johncms\Auth\External\UserIdentityRepositoryInterface;
use Johncms\Auth\Impersonation\ImpersonationSettings;
use Johncms\Auth\Impersonation\ImpersonationSettingsFactory;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentAuthEventRepository;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentAuthSessionRepository;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentPasswordResetTokenRepository;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentUserIdentityRepository;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentRoleRepository;
use Johncms\Auth\Password\PasswordHasherFactory;
use Johncms\Auth\Password\PasswordHasherInterface;
use Johncms\Auth\Password\PasswordResetTokenRepositoryInterface;
use Johncms\Auth\Session\AuthSessionRepositoryInterface;
use Johncms\Auth\Session\SessionSettings;
use Johncms\Auth\Session\SessionSettingsFactory;
use Johncms\Auth\Throttling\CacheLoginThrottle;
use Johncms\Auth\Throttling\LoginThrottleInterface;
use Johncms\Cache\CacheInterface;
use Johncms\Cache\CachePoolFactory;
use Johncms\Cache\CacheSettings;
use Johncms\Cache\CacheSettingsFactory;
use Johncms\Captcha\CaptchaProviderRegistry;
use Johncms\Counters;
use Johncms\CountersFactory;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Johncms\Database\ConnectionInterface;
use Johncms\Database\PdoConnection;
use Johncms\Database\PdoFactory;
use Johncms\Database\Schema\Adapters\BlueprintCompiler;
use Johncms\Database\Schema\Adapters\IlluminateSchema;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Database\SchemaBuilderFactory;
use Johncms\Files\FileRepositoryInterface;
use Johncms\Files\FileStore;
use Johncms\Files\Infrastructure\Persistence\Repository\EloquentFileRepository;
use Johncms\Image\ImageProcessorInterface;
use Johncms\Image\InterventionImageProcessor;
use Johncms\Logs\LoggerFactory;
use Johncms\Mail\MailDsnResolver;
use Johncms\Mail\MailFactory;
use Johncms\Mail\Queue\EloquentEmailQueue;
use Johncms\Mail\Queue\EmailQueueInterface;
use Johncms\Mail\Queue\MailQueueInterface;
use Johncms\Mail\Queue\MailQueueSettings;
use Symfony\Component\Mime\HtmlToTextConverter\DefaultHtmlToTextConverter;
use Symfony\Component\Mime\HtmlToTextConverter\HtmlToTextConverterInterface;
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
use Johncms\Security\HtmlPolicyRegistry;
use Johncms\Security\HtmlPurifierFactory;
use Johncms\Security\HtmlSanitizer;
use Johncms\Security\HtmlSanitizerInterface;
use Johncms\Smilies\SmiliesRenderer;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Storage\StorageInterface;
use Johncms\Storage\StorageRegistry;
use Johncms\Storage\StorageRegistryInterface;
use Johncms\Storage\StorageSettings;
use Johncms\Storage\StorageSettingsFactory;
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
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;
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
                // Value objects of the content pipeline: what a caller asks for and what a
                // provider answers, both carrying scalar constructor arguments.
                ROOT_PATH . 'system/src/Content/ContentContext.php',
                ROOT_PATH . 'system/src/Content/Embed/EmbeddedMedia.php',
                // The cache settings and the enums behind them are value objects, and the
                // implementation takes an intersection-typed pool the container cannot resolve:
                // both are assembled by CachePoolFactory instead.
                ROOT_PATH . 'system/src/Cache/CacheSettings.php',
                ROOT_PATH . 'system/src/Cache/CacheDriver.php',
                ROOT_PATH . 'system/src/Cache/TagsStorage.php',
                ROOT_PATH . 'system/src/Cache/SymfonyCache.php',
                ROOT_PATH . 'system/src/Cache/UnsupportedCacheDriverException.php',
                // Exceptions are never services: those with scalar constructor arguments
                // (HttpRedirectException) break the container compilation when autowired.
                ROOT_PATH . 'system/src/Exceptions',
                // How a migration describes a table: value objects carrying a column name
                // and a length, an enum per kind, and the two adapters that read them. The
                // adapters are registered by hand below; the description is never a service.
                ROOT_PATH . 'system/src/Database/Schema',
                ROOT_PATH . 'system/src/Image/ImageProcessingException.php',
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
                ROOT_PATH . 'system/src/Http/CachedImageResponse.php',
                // The request is not a service: it belongs to a cycle, and a container-built one
                // would be an empty request assembled from the globals of whoever asked first.
                ROOT_PATH . 'system/src/Http/Request.php',
                ROOT_PATH . 'system/src/Http/Pagination/Pagination.php',
                ROOT_PATH . 'system/src/Http/UploadedFileDTO.php',
                ROOT_PATH . 'system/src/Security/ClientInfoDTO.php',
                // A policy is a value object a module builds itself, and the exception carries a
                // scalar message: neither is a service the container can assemble.
                ROOT_PATH . 'system/src/Security/HtmlPolicyDefinition.php',
                ROOT_PATH . 'system/src/Security/UnknownHtmlPolicyException.php',
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
                ROOT_PATH . 'system/src/Auth/Events/AuthEventType.php',
                // Built by SessionSettingsFactory from config, not autowired from its scalars.
                ROOT_PATH . 'system/src/Auth/Session/SessionSettings.php',
                // Same story: built by ImpersonationSettingsFactory from config.
                ROOT_PATH . 'system/src/Auth/Impersonation/ImpersonationSettings.php',
                ROOT_PATH . 'system/src/Auth/Impersonation/ImpersonationNotAllowedException.php',
                ROOT_PATH . 'system/src/Auth/Impersonation/ImpersonationBannerDTO.php',
                // Value objects and enums of the external sign-in layer; the providers themselves
                // are services and stay autowired.
                ROOT_PATH . 'system/src/Auth/External/ExternalAuthContextDTO.php',
                ROOT_PATH . 'system/src/Auth/External/ExternalCallbackDTO.php',
                ROOT_PATH . 'system/src/Auth/External/ExternalIdentityDTO.php',
                ROOT_PATH . 'system/src/Auth/External/ExternalAuthResultDTO.php',
                ROOT_PATH . 'system/src/Auth/External/ExternalAuthStatus.php',
                ROOT_PATH . 'system/src/Auth/External/ProviderSettings.php',
                ROOT_PATH . 'system/src/Auth/External/ExternalAuthException.php',
                ROOT_PATH . 'system/src/Auth/External/ExternalAccountConflictException.php',
                // The body of one rendered message and the exception of a broken mail
                // configuration: value objects carrying scalars, not services.
                ROOT_PATH . 'system/src/Mail/RenderedEmailDTO.php',
                // Built by MailFactory from the configuration, with the addresses it carries.
                ROOT_PATH . 'system/src/Mail/RedirectAllMessages.php',
                ROOT_PATH . 'system/src/Mail/Queue/QueuedEmailDTO.php',
                ROOT_PATH . 'system/src/Mail/Exception',
                ROOT_PATH . 'system/src/Mail/Schema',
                // The storage layer: settings and enums are value objects built by
                // StorageSettingsFactory, and a disk carries the scalars of its configuration —
                // StorageFactory assembles it, the container cannot.
                ROOT_PATH . 'system/src/Storage/DiskSettings.php',
                ROOT_PATH . 'system/src/Storage/StorageSettings.php',
                ROOT_PATH . 'system/src/Storage/StorageDriver.php',
                ROOT_PATH . 'system/src/Storage/FlysystemStorage.php',
                ROOT_PATH . 'system/src/Storage/StorageException.php',
                ROOT_PATH . 'system/src/Storage/UnknownStorageDiskException.php',
                ROOT_PATH . 'system/src/Storage/UnsupportedStorageDriverException.php',
                // The captcha layer: challenges, verdicts and the description of a provider's
                // settings are value objects, and the options are read from config on demand.
                // The providers themselves are services and stay autowired.
                ROOT_PATH . 'system/src/Captcha/CaptchaChallenge.php',
                ROOT_PATH . 'system/src/Captcha/CaptchaResult.php',
                ROOT_PATH . 'system/src/Captcha/CaptchaFailure.php',
                ROOT_PATH . 'system/src/Captcha/CaptchaSettingField.php',
                ROOT_PATH . 'system/src/Captcha/CaptchaSettingType.php',
                ROOT_PATH . 'system/src/Captcha/CaptchaProviderOptions.php',
                ROOT_PATH . 'system/src/Captcha/CaptchaException.php',
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
    $services->set(FileRepositoryInterface::class, EloquentFileRepository::class);
    $services->set(FileStore::class, FileStore::class);
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
    // The two contracts a migration is written against. Neither names the library underneath,
    // so a migration written today keeps working when that library is replaced.
    $services->alias(ConnectionInterface::class, PdoConnection::class);
    $services->set(BlueprintCompiler::class);
    $services->set(SchemaInterface::class, IlluminateSchema::class);
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
    $services->set(AuthEventRepositoryInterface::class, EloquentAuthEventRepository::class);
    $services->set(AuthEventLoggerInterface::class, AuthEventLogger::class);
    // Reads the algorithm from config at instantiation: baking it into the compiled container
    // would keep a changed configuration from ever taking effect.
    $services->set(PasswordHasherInterface::class)->factory(service(PasswordHasherFactory::class));
    $services->set(LoginThrottleInterface::class, CacheLoginThrottle::class);
    $services->set(AuthSessionRepositoryInterface::class, EloquentAuthSessionRepository::class);
    $services->set(RoleRepositoryInterface::class, EloquentRoleRepository::class);
    // Read from config at instantiation rather than while the container is built: the built
    // container is cached, and anything resolved there would freeze the configuration into it.
    $services->set(SessionSettings::class)->factory(service(SessionSettingsFactory::class));
    $services->set(ImpersonationSettings::class)->factory(service(ImpersonationSettingsFactory::class));
    $services->set(UserIdentityRepositoryInterface::class, EloquentUserIdentityRepository::class);
    // A module adds a service to sign in with by tagging its provider; the registry is what the
    // buttons and the callback route are built from.
    $services->set(ExternalIdentityProviderRegistry::class)
        ->arg('$providers', tagged_iterator('johncms.auth.external_provider'));
    // The HTTP client the OAuth providers talk through. Registered under the interface so a test
    // or a module can put a different one in its place.
    $services->set(HttpClientInterface::class)->factory([HttpClient::class, 'create']);

    // A module adds a captcha of its own by implementing CaptchaProviderInterface; the tag is put
    // on it by PSRContainerFactory, so its services.php needs nothing special. The registry is
    // what the forms and the settings page are built from.
    $services->set(CaptchaProviderRegistry::class)
        ->arg('$providers', tagged_iterator('johncms.captcha_provider'));

    $services->set(AntifloodCheckerInterface::class, AntifloodChecker::class)->autowire();
    $services->set(RequestRateLogInterface::class, FileRequestRateLog::class);
    $services->set(SmiliesRendererInterface::class, SmiliesRenderer::class);
    $services->set(UserPlaceFormatterInterface::class, UserPlaceFormatter::class)->autowire();
    $services->set(IgnoreListCheckerInterface::class, IgnoreListChecker::class);
    $services->set(DateFormatterInterface::class, DateFormatter::class)->autowire();
    $services->set(NavChain::class)->factory([NavChain::class, 'create']);
    // Which library resizes the pictures is settled here and nowhere else: everything that
    // stores an upload asks for the interface.
    $services->set(ImageProcessorInterface::class, InterventionImageProcessor::class);
    $services->set(Ads::class)->factory(service(AdsFactory::class));
    $services->set(Csrf::class)->factory([Csrf::class, 'create']);
    // Reads config/csrf.php: an array argument the container cannot autowire.
    $services->set(CsrfExemptions::class)->factory([CsrfExemptions::class, 'create']);
    $services->set('counters', Counters::class)->factory(service(CountersFactory::class));
    // The counters are built by a factory under a string id; the alias is what lets a service or
    // a controller ask for them by type.
    $services->alias(Counters::class, 'counters');
    $services->set(MailDsnResolver::class);
    $services->set(MailFactory::class)->factory([MailFactory::class, 'create']);
    // Reads the `queue` section of the mail configuration: an array the container cannot autowire.
    $services->set(MailQueueSettings::class)->factory([MailQueueSettings::class, 'fromConfig']);
    $services->set(EmailQueueInterface::class, EloquentEmailQueue::class)->autowire();
    // The same queue seen from the other end: what modules put messages into.
    $services->alias(MailQueueInterface::class, EmailQueueInterface::class);
    $services->set(HtmlPurifierFactory::class);
    // A module declares an HTML policy of its own by registering an HtmlPolicyProviderInterface;
    // the tag is put on it by PSRContainerFactory, so its services.php needs nothing special.
    $services->set(HtmlPolicyRegistry::class)
        ->arg('$providers', tagged_iterator('johncms.html_policy_provider'));
    $services->set(HtmlSanitizerInterface::class, HtmlSanitizer::class)->autowire();

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
    // The plain text alternative of a message whose template does not provide one of its own.
    // Installing league/html-to-markdown and swapping this for LeagueHtmlToMarkdownConverter
    // gives a better rendering of such text; the templates shipped with the theme write it
    // themselves and do not go through the converter at all.
    $services->set(HtmlToTextConverterInterface::class, DefaultHtmlToTextConverter::class);

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
    // The cache of the CMS. Read from config at instantiation rather than while the
    // container is built: the built container is cached, and a driver resolved there would be
    // frozen into it.
    $services->set(CacheSettings::class)->factory(service(CacheSettingsFactory::class));
    $services->set(CacheInterface::class)->factory([service(CachePoolFactory::class), 'create']);
    // The disks the CMS stores files on, read from config at instantiation for the same reason.
    $services->set(StorageSettings::class)->factory(service(StorageSettingsFactory::class));
    $services->set(StorageRegistryInterface::class, StorageRegistry::class);
    // The default disk, for the code that knows which disk it works with at wiring time. Only
    // what learns the name at runtime — a row of `files` — goes through the registry.
    $services->set(StorageInterface::class)->factory([service(StorageRegistryInterface::class), 'disk']);
    $services->set(SitemapGenerator::class)->arg('$moduleProviders', tagged_iterator('johncms.sitemap_provider'));
    // The content pipeline. Both registries are handed the services a module tagged by simply
    // implementing the interface — see PSRContainerFactory::registerExtensionPoints().
    $services->set(ContentTransformerRegistry::class)
        ->arg('$transformers', tagged_iterator('johncms.content_transformer'));
    $services->set(EmbedProviderRegistry::class)
        ->arg('$providers', tagged_iterator('johncms.embed_provider'));
    $services->set(ContentRendererInterface::class, ContentRenderer::class)->autowire();
    $services->set(ColorScheme::class);
    $services->set(\Johncms\Scheduler\ScheduleMutexInterface::class, \Johncms\Scheduler\FileScheduleMutex::class);
    $services->set(\Johncms\Scheduler\ScheduledTaskRegistry::class)
        ->arg('$commands', tagged_iterator('johncms.console_command'));
    $services->set(\Johncms\AdminTasks\AdminTaskRegistry::class)
        ->arg('$commands', tagged_iterator('johncms.console_command'));
    $services->set(\Johncms\AdminTasks\AdminTaskRunner::class)
        ->arg('$mutex', service(\Johncms\AdminTasks\FileAdminTaskMutex::class));
};
