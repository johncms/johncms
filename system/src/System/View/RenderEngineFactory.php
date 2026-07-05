<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\View;

use Johncms\Security\Csrf;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Extension\Avatar;
use Psr\Container\ContainerInterface;
use Johncms\System\i18n\Translator;

class RenderEngineFactory
{
    public function __invoke(ContainerInterface $container): Render
    {
        $config = config('johncms');
        $engine = new Render('phtml');

        if ($this->isAdmin()) {
            $engine->setTheme('admin');
            $engine->addFolder('system', realpath(THEMES_PATH . 'admin/templates/system'));
        } else {
            $engine->setTheme($config['skindef']);
            $engine->addFolder('system', realpath(THEMES_PATH . 'default/templates/system'));
        }

        $engine->loadExtension($container->get(Assets::class));
        $engine->loadExtension($container->get(Avatar::class));
        $engine->addData(
            [
                'container'  => $container,
                'config'     => $config,
                'locale'     => $container->get(Translator::class)->getLocale(),
                'user'       => $container->get(User::class),
                'tools'      => $container->get(Tools::class),
                'csrf_token' => $container->get(Csrf::class)->getToken(),
            ]
        );

        return $engine;
    }

    private function isAdmin(): bool
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (! is_string($path)) {
            return false;
        }
        return $path === '/admin' || str_starts_with($path, '/admin/');
    }
}
