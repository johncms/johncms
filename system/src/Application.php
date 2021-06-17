<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms;

use Illuminate\Container\Container;
use Johncms\System\Container\Config;
use Johncms\System\Http\Request;
use Johncms\System\Http\ResponseFactory;
use Johncms\System\Router\RouterFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;

class Application
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function run(): void
    {
        $this->registerSystemProviders();
        $this->runModuleProviders();
        $router = $this->container->get(RouterFactory::class);
        (new SapiEmitter())->emit($router->dispatch());
    }

    private function runModuleProviders(): void
    {
        $config = $this->container->get(Config::class);
        $providers = $config['providers'] ?? [];
        foreach ($providers as $provider) {
            /** @var ServiceProvider $module_providers */
            $module_providers = $this->container->get($provider);
            $module_providers->register();
        }
    }

    public function registerSystemProviders(): void
    {
        $this->container->singleton(ContainerInterface::class, fn() => Container::getInstance());
        $this->container->singleton(ServerRequestInterface::class, fn() => Request::fromGlobals());
        $this->container->singleton(Request::class, fn() => $this->container->get(ServerRequestInterface::class));
        $this->container->bind(ResponseFactoryInterface::class, ResponseFactory::class);
        $this->container->bind(CacheInterface::class, Cache::class);
        $this->container->singleton(RouterFactory::class, RouterFactory::class);

        $this->container->singleton(
            Config::class,
            function () {
                return (new Config())();
            }
        );
    }
}
