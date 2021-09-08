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

use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Application;
use Johncms\Container\ContainerFactory;
use Johncms\Database\Migration;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    public Generator $faker;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->faker = Factory::create();
        parent::__construct($name, $data, $dataName);
    }

    public function runMigrations()
    {
        $migrations = new Migration();
        $migrations->run();
    }

    public function dropTables()
    {
        $schema = Capsule::schema();
        $schema->dropAllTables();
    }

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
        $application = new Application($container);
        $application->run();
    }
}
