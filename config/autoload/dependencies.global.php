<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api;
use Johncms\View\Extension\Assets;

return [
    'dependencies' => [
        'factories' => [
            Api\BbcodeInterface::class      => Johncms\Utility\Bbcode::class,
            Api\ConfigInterface::class      => Johncms\Config\ConfigFactory::class,
            Api\EnvironmentInterface::class => Johncms\Http\Environment::class,
            Api\ToolsInterface::class       => Johncms\Utility\Tools::class,
            Api\UserInterface::class        => Johncms\Users\UserFactory::class,
            Assets::class                   => Assets::class,
            FastRoute\RouteCollector::class => Johncms\Router\RouteCollectorFactory::class,
            League\Plates\Engine::class     => Johncms\View\PlatesEngineFactory::class,
            PDO::class                      => Johncms\Database\PdoFactory::class,
            'counters'                      => Johncms\Utility\Counters::class,
        ],
    ],
];
