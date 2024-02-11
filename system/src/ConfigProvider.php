<?php

declare(strict_types=1);

namespace Johncms;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Johncms\Console\Commands\ClearCacheCommand;
use Johncms\Console\Commands\MakeMigrationCommand;
use Johncms\Console\Commands\MigrateCommand;
use Johncms\Console\Commands\TranslateGenerateCommand;
use Johncms\Console\Commands\TranslateScanCommand;
use Johncms\Database\DatabaseAbstractServiceProvider;
use Johncms\Database\PdoFactory;
use Johncms\Debug\DebugBar;
use Johncms\Debug\DebugServiceProvider;
use Johncms\Events\DispatcherFactory;
use Johncms\Files\Filesystem;
use Johncms\Http\IpLogger;
use Johncms\Http\Request;
use Johncms\Http\RequestFactory;
use Johncms\Http\ResponseFactory;
use Johncms\Http\Session;
use Johncms\i18n\Translator;
use Johncms\i18n\TranslatorServiceFactory;
use Johncms\i18n\TranslatorAbstractServiceProvider;
use Johncms\Log\ExceptionHandlers;
use Johncms\Log\Logger;
use Johncms\Media\MediaEmbed;
use Johncms\Middlewares\CsrfMiddleware;
use Johncms\Middlewares\SessionMiddleware;
use Johncms\Router\Router;
use Johncms\Router\RouteRequirements;
use Johncms\Router\RouterFactory;
use Johncms\Security\HTMLPurifier;
use Johncms\Settings\SiteSettings;
use Johncms\Users\AuthProviders\CookiesAuthProvider;
use Johncms\Users\AuthProviders\SessionAuthProvider;
use Johncms\Users\Ban\SystemBanTypes;
use Johncms\Utility\SerializerFactory;
use Johncms\View\AdminRenderEngineFactory;
use Johncms\View\MetaTagManager;
use Johncms\View\Render;
use Johncms\View\RenderEngineFactory;
use PDO;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies'   => $this->getDependencies(),
            'middleware'     => $this->getMiddlewares(),
            'providers'      => [
                DatabaseAbstractServiceProvider::class,
                TranslatorAbstractServiceProvider::class,
                DebugServiceProvider::class,
            ],
            'commands'       => $this->getCommands(),
            'auth_providers' => $this->getAuthProviders(),
            'bans'           => [
                'ban_types' => [
                    SystemBanTypes::class,
                ],
            ],
        ];
    }

    private function getDependencies(): array
    {
        return [
            'aliases' => [
                Request::class             => ServerRequestInterface::class,
                RequestFactory::class      => ServerRequestInterface::class,
                Factory::class             => Render::class,
                'view'                     => Render::class,
                SerializerInterface::class => Serializer::class,
            ],

            'shared' => [
                IpLogger::class                 => IpLogger::class,
                ResponseFactoryInterface::class => ResponseFactory::class,
                CacheInterface::class           => Cache::class,
                Session::class                  => Session::class,
                MetaTagManager::class           => MetaTagManager::class,
                SiteSettings::class             => SiteSettings::class,
                DebugBar::class                 => DebugBar::class,
                ExceptionHandlers::class        => ExceptionHandlers::class,
                RouteRequirements::class        => RouteRequirements::class,
            ],

            'factories' => [
                PDO::class                      => PdoFactory::class,
                Render::class                   => RenderEngineFactory::class,
                AdminRenderEngineFactory::class => AdminRenderEngineFactory::class,
                ServerRequestInterface::class   => RequestFactory::class,
                Translator::class               => TranslatorServiceFactory::class,
                Users\User::class               => Users\UserFactory::class,
                Filesystem::class               => Filesystem::class,
                MediaEmbed::class               => MediaEmbed::class,
                LoggerInterface::class          => Logger::class,
                Dispatcher::class               => DispatcherFactory::class,
                Serializer::class               => SerializerFactory::class,
                \HTMLPurifier::class            => HTMLPurifier::class,
                Router::class                   => RouterFactory::class,
            ],
        ];
    }

    private function getMiddlewares(): array
    {
        return [
            SessionMiddleware::class,
            CsrfMiddleware::class,
        ];
    }

    private function getCommands(): array
    {
        return [
            MakeMigrationCommand::class,
            MigrateCommand::class,
            ClearCacheCommand::class,
            TranslateScanCommand::class,
            TranslateGenerateCommand::class,
        ];
    }

    private function getAuthProviders(): array
    {
        return [
            SessionAuthProvider::class,
            CookiesAuthProvider::class,
        ];
    }
}
