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

use FastRoute\RouteCollector;
use Johncms\System\{
    Database\PdoFactory,
    Http\Environment,
    Http\Request,
    Http\RequestFactory,
    i18n\Translator,
    i18n\TranslatorServiceFactory,
    Router\RouteCollectorFactory,
    Users\User,
    Users\UserFactory,
    View\Render,
    View\RenderEngineFactory
};
use Johncms\System\View\Extension\{
    Assets,
    Avatar
};
use PDO;
use Psr\Http\Message\ServerRequestInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    private function getDependencies(): array
    {
        return [
            'aliases' => [
                ServerRequestInterface::class => Request::class,
            ],

            'factories' => [
                Assets::class         => Assets::class,
                Avatar::class         => Avatar::class,
                Environment::class    => Environment::class,
                RouteCollector::class => RouteCollectorFactory::class,
                PDO::class            => PdoFactory::class,
                Render::class         => RenderEngineFactory::class,
                Request::class        => RequestFactory::class,
                Translator::class     => TranslatorServiceFactory::class,
                User::class           => UserFactory::class,
                Users\User::class     => Users\UserFactory::class,
            ],

            'invokables' => [],
        ];
    }
}
