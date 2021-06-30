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

use Gettext\TranslatorFunctions;
use Illuminate\Container\Container;
use Johncms\i18n\Translator;
use Johncms\Modules\Modules;
use Johncms\Router\RouterFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use PDO;

class Application
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function run(): Application
    {
        di(PDO::class);
        (new Modules())->registerAutoloader();
        $this->runModuleProviders();
        $this->setupTranslator();
        return $this;
    }

    private function runModuleProviders(): void
    {
        $config = $this->container->get('config');
        $providers = $config['providers'] ?? [];
        foreach ($providers as $provider) {
            /** @var ServiceProvider $module_providers */
            $module_providers = $this->container->get($provider);
            $module_providers->register();
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
    }

    public function handleRequest(): void
    {
        $router = $this->container->get(RouterFactory::class);
        (new SapiEmitter())->emit($router->dispatch());
    }
}
