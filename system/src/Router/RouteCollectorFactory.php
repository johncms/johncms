<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Router;

use FastRoute\RouteCollector;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteParser\Std as RouteParser;
use Psr\Container\ContainerInterface;

class RouteCollectorFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new RouteCollector(
            new RouteParser(),
            new GroupCountBased()
        );
    }
}
