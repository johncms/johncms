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
use League\Plates\Engine;
use Johncms\Api\ConfigInterface;
use Psr\Container\ContainerInterface;
use Zend\I18n\Translator\Translator;

class PlatesEngineFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $plates = new Engine;
        $plates->setFileExtension('phtml');
        $plates->addFolder('system', ROOT_PATH . 'themes/default/templates');
        $plates->addData([
            'config'    => $container->get(ConfigInterface::class),
            'container' => $container,
            'locale'    => $container->get(Translator::class)->getLocale(),
            'tools'     => $container->get(ToolsInterface::class),
            'user'      => $container->get(UserInterface::class),
        ]);

        return $plates;
    }
}
