<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Database;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use PDO;
use Psr\Container\ContainerInterface;

class PdoFactory
{
    public function __invoke(ContainerInterface $container): PDO
    {
        $config = $container->has('database')
            ? (array) $container->get('database')
            : [];

        $capsule = new Capsule();
        $capsule->addConnection(
            [
                'driver'    => $config['db_driver'] ?? 'mysql',
                'host'      => $config['db_host'] ?? 'localhost',
                'port'      => $config['db_port'] ?? '3306',
                'database'  => $config['db_name'] ?? 'johncms',
                'username'  => $config['db_user'] ?? 'root',
                'password'  => $config['db_pass'] ?? '',
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'timezone'  => '+00:00',
                'strict'    => DB_STRICT_MODE,
                'engine'    => 'InnoDB ROW_FORMAT=DYNAMIC',
            ]
        );

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        if (DEBUG) {
            $capsule->getDatabaseManager()->enableQueryLog();
        }

        try {
            return $capsule->getDatabaseManager()->getPdo();
        } catch (Exception $exception) {
            die('<b>Error connecting to the database server:</b><br>' . $exception->getMessage());
        }
    }
}
