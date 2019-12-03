<?php

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\View;

use Johncms\Api\ToolsInterface;
use Johncms\Api\UserInterface;
use Johncms\View\Extension\Assets;
use Johncms\Api\ConfigInterface;
use Psr\Container\ContainerInterface;
use Zend\I18n\Translator\Translator;

class RenderEngineFactory
{
    public function __invoke(ContainerInterface $container): Render
    {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        $engine = new Render('phtml');
        $engine->setTheme($config->skindef);
        $engine->addFolder('system', realpath(ROOT_PATH . 'themes/default/templates/system'));
        $engine->loadExtension($container->get(Assets::class));
        $engine->addData(
            [
                'container' => $container,
                'config'    => $container->get(ConfigInterface::class),
                'locale'    => $container->get(Translator::class)->getLocale(),
                'tools'     => $container->get(ToolsInterface::class),
                'user'      => $container->get(UserInterface::class),
            ]
        );

        return $engine;
    }
}
