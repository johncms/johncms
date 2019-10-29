<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\View;

use Johncms\Api\ToolsInterface;
use Johncms\Api\UserInterface;
use Johncms\View\Extension\Assets;
use League\Plates\Engine;
use Johncms\Api\ConfigInterface;
use Psr\Container\ContainerInterface;
use Zend\I18n\Translator\Translator;

class PlatesEngineFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $engine = new Engine;
        $engine->setFileExtension('phtml');
        $engine->addFolder('system', ROOT_PATH . 'themes/default/templates');
        $engine->loadExtension($container->get(Assets::class));

        $engine->addData([
            'container' => $container,
            'config'    => $container->get(ConfigInterface::class),
            'locale'    => $container->get(Translator::class)->getLocale(),
            'tools'     => $container->get(ToolsInterface::class),
            'user'      => $container->get(UserInterface::class),
        ]);

        return $engine;
    }
}
