<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Tests;

use Johncms\Container\ContainerFactory;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $container = ContainerFactory::getContainer();

        // Replace config
        $config = $container->get('config');
        $config['pdo']['db_driver'] = 'sqlite';
        $config['pdo']['db_name'] = ':memory:';
        $config['pdo']['db_user'] = '';
        $config['pdo']['db_pass'] = '';

        $container->instance('config', $config);
    }
}
