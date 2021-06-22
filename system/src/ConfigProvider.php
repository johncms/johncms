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

use Johncms\Files\Filesystem;
use Johncms\Media\MediaEmbed;
use Johncms\System\{Database\PdoFactory, Http\Environment, Http\Request, Http\RequestFactory, Http\ResponseFactory, i18n\Translator, i18n\TranslatorServiceFactory, Users\User, Users\UserFactory, View\Render, View\RenderEngineFactory};
use Johncms\System\View\Extension\{Assets, Avatar};
use PDO;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'middleware'   => [],
            'providers'    => [],
        ];
    }

    private function getDependencies(): array
    {
        return [
            'aliases' => [
                Request::class        => ServerRequestInterface::class,
                RequestFactory::class => ServerRequestInterface::class,
            ],

            'factories' => [
                Assets::class                   => Assets::class,
                Avatar::class                   => Avatar::class,
                Environment::class              => Environment::class,
                PDO::class                      => PdoFactory::class,
                Render::class                   => RenderEngineFactory::class,
                ServerRequestInterface::class   => RequestFactory::class,
                Translator::class               => TranslatorServiceFactory::class,
                User::class                     => UserFactory::class,
                Users\User::class               => Users\UserFactory::class,
                Filesystem::class               => Filesystem::class,
                ResponseFactoryInterface::class => ResponseFactory::class,
                CacheInterface::class           => Cache::class,
                MediaEmbed::class               => MediaEmbed::class,
            ],
        ];
    }
}
