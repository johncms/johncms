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

use Illuminate\Contracts\Events\Dispatcher;
use Johncms\Log\ExceptionHandlers;
use Johncms\Router\Router;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class Application
{
    public const BEFORE_HANDLE_REQUEST_EVENT = 'app.beforeHandleRequest';
    public const AFTER_HANDLE_REQUEST_EVENT = 'app.afterHandleRequest';

    public function __construct(
        private ContainerInterface $container,
        private Dispatcher $events,
        private ExceptionHandlers $exceptionHandlers
    ) {
        $this->exceptionHandlers->registerHandlers();
    }

    public function run(): Application
    {
        $this->runModuleProviders();
        return $this;
    }

    private function runModuleProviders(): void
    {
        $config = $this->container->get('config');
        $providers = $config['providers'] ?? [];
        foreach ($providers as $provider) {
            /** @var AbstractServiceProvider $moduleProviders */
            $moduleProviders = $this->container->get($provider);
            $moduleProviders->register();
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function handleRequest(): void
    {
        $router = $this->container->get(Router::class);
        $this->events->dispatch(Application::BEFORE_HANDLE_REQUEST_EVENT);

        $response = $router->handleRequest();
        (new SapiEmitter())->emit($response);

        // Dispatch after handle request event
        $this->events->dispatch(Application::AFTER_HANDLE_REQUEST_EVENT, $response);
    }
}
