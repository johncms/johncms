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

use Carbon\Carbon;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Facades\DB;
use Johncms\Debug\DebugBar;
use Johncms\Http\Request;
use Johncms\i18n\Translator;
use Johncms\Log\ExceptionHandlers;
use Johncms\Router\RouterFactory;
use Johncms\Users\User;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use PDO;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class Application
{
    public function __construct(
        private ContainerInterface $container
    ) {
        $container->get(ExceptionHandlers::class)->registerHandlers();
    }

    public function run(): Application
    {
        $this->setupDatabase();
        $this->runModuleProviders();
        $this->setupTranslator();
        return $this;
    }

    private function setupDatabase(): void
    {
        di(PDO::class);
        $connection = Manager::connection();
        DB::swap($connection);
    }

    private function runModuleProviders(): void
    {
        $config = $this->container->get('config');
        $providers = $config['providers'] ?? [];
        foreach ($providers as $provider) {
            /** @var ServiceProvider $moduleProviders */
            $moduleProviders = $this->container->get($provider);
            $moduleProviders->register();
        }
    }

    private function setupTranslator(): void
    {
        // Register the system languages domain and folder
        $translator = di(Translator::class);
        $translator->addTranslationDomain('system', __DIR__ . '/../locale');
        $translator->defaultDomain('system');
        // Register language helpers
        TranslatorFunctions::register($translator);
        Carbon::setLocale($translator->getLocale());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function handleRequest(): void
    {
        $this->container->bind(RouterFactory::class, RouterFactory::class, true);
        $router = $this->container->get(RouterFactory::class);

        $request = di(Request::class);
        $debug = (DEBUG_FOR_ALL || (DEBUG && di(User::class)?->isAdmin())) && ! str_starts_with($request->getUri()->getPath(), '/_debugbar/');

        // Initialize the debugbar if necessary
        if ($debug) {
            $debugBar = di(DebugBar::class);
            $debugBar->addBootingTime();
            $debugBar->startApplicationMeasure();
            header('phpdebugbar-id: ' . $debugBar->getCurrentRequestId());
            $getJavascriptRenderer = $debugBar->getJavascriptRenderer();
            $getJavascriptRenderer->setBindAjaxHandlerToXHR();
            $getJavascriptRenderer->addAssets(['/themes/default/assets/debugbar/custom.css'], ['/themes/default/assets/debugbar/queryWidget.js']);
        }

        // Handle request
        $response = $router->dispatch();
        (new SapiEmitter())->emit($response);

        // Collect data for debugbar and render html
        if ($debug) {
            $contentType = $response->getHeader('content-type')[0] ?? 'text/html';
            if ($request->isXmlHttpRequest() || $contentType === 'application/json') {
                $debugBar->stackData();
            } else {
                $getJavascriptRenderer->renderOnShutdownWithHead();
            }
        }
    }
}
