<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Boots an isolated in-memory SQLite database and wires it into Eloquent as the
 * global connection. Each test gets a fresh, empty schema; call bootDatabase()
 * in setUp() and shutdownDatabase() in tearDown().
 */
trait BootsInMemoryDatabase
{
    private ?Capsule $capsule = null;

    protected function bootDatabase(): Capsule
    {
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Enforce foreign keys (SQLite has them off by default).
        Capsule::connection()->getPdo()->exec('PRAGMA foreign_keys = ON;');

        $this->capsule = $capsule;

        return $capsule;
    }

    protected function shutdownDatabase(): void
    {
        if ($this->capsule !== null) {
            $this->capsule->getDatabaseManager()->disconnect();
            $this->capsule = null;
        }
    }
}
