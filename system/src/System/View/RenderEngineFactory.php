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

use Johncms\Http\CurrentPage;
use Johncms\Security\Csrf;
use Johncms\Users\User;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Extension\Avatar;
use Johncms\System\View\Extension\Formatter;
use Johncms\System\View\Extension\Vite;
use Psr\Container\ContainerInterface;
use Johncms\System\i18n\Translator;

class RenderEngineFactory
{
    public function __invoke(ContainerInterface $container): Render
    {
        $config = config('johncms');
        $engine = new Render('phtml');

        $currentPage = $container->get(CurrentPage::class);
        $engine->setThemeResolver(
            static fn (): string => $currentPage->isAdminArea() ? 'admin' : (string) $config['skindef']
        );
        $engine->addFolder('system', realpath(THEMES_PATH . 'default/templates/system'));

        $engine->loadExtension($container->get(Assets::class));
        $engine->loadExtension($container->get(Avatar::class));
        $engine->loadExtension($container->get(Vite::class));
        $engine->loadExtension($container->get(Formatter::class));
        $engine->addData(
            [
                'container'  => $container,
                'config'     => $config,
                'locale'     => $container->get(Translator::class)->getLocale(),
                'user'       => $container->get(User::class),
                'csrf_token' => $container->get(Csrf::class)->getToken(),
            ]
        );

        return $engine;
    }
}
