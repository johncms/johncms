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

return [
    'dependencies' => [
        'factories' => [
            Api\BbcodeInterface::class        => Johncms\Utility\Bbcode::class,
            Api\EnvironmentInterface::class   => Johncms\Http\Environment::class,
            Api\ToolsInterface::class         => Johncms\Utility\Tools::class,
            Api\NavChainInterface::class      => Johncms\Utility\NavChain::class,
            FastRoute\RouteCollector::class   => Johncms\Router\RouteCollectorFactory::class,
            'counters'                        => Johncms\Utility\Counters::class,
        ],
    ],
];
