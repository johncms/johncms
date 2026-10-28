<?php

declare(strict_types=1);

namespace Johncms\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * The Eloquent capsule, configured and set as global. Building it does not connect to the
 * database: the connection opens on the first query, so a console command that never reaches
 * the database keeps working without one.
 */
final class CapsuleFactory
{
    public function __invoke(): Capsule
    {
        $config = config('pdo', []);

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

        return $capsule;
    }
}
