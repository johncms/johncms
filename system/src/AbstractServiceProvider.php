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

use Psr\Container\ContainerInterface;

abstract class AbstractServiceProvider
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    abstract public function register(): void;
}
