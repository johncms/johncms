<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Capsule\Manager as Capsule;

final class CollectionWriterDatabase
{
    public static function boot(): Capsule
    {
        $config = FunctionalDatabase::fromEnvironment()->config();
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'mysql', 'host' => $config['db_host'], 'port' => $config['db_port'],
            'database' => $config['db_name'], 'username' => $config['db_user'], 'password' => $config['db_pass'],
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'engine' => 'InnoDB',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        return $capsule;
    }
}
