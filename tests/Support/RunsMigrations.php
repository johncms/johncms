<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\Migrations\Migration;
use Johncms\Database\Migrations\MigrationFile;
use Johncms\Database\Migrations\MigrationLocator;
use Johncms\Database\Migrations\ModuleMigrationSourceProvider;
use Johncms\Database\Migrations\SystemMigrationSourceProvider;
use Johncms\Database\PdoConnection;
use Johncms\Database\Schema\Adapters\IlluminateSchema;
use RuntimeException;

/**
 * Builds the schema of a test the way a real installation gets it: by running the migrations.
 *
 * There is no second description of the tables to keep in step with the first — a test asks for
 * the source it needs and gets exactly what a site has.
 *
 * The migrations are run directly rather than through the migrator, because a test has no use for
 * a journal: the database is thrown away at the end of it.
 *
 * A test may ask for single migrations of a source instead of all of them, which says what it
 * depends on; migrateEverything() builds the schema of a whole site, which is what the functional
 * suite needs.
 *
 * Use together with BootsInMemoryDatabase, after bootDatabase().
 */
trait RunsMigrations
{
    /**
     * The schema of a whole site: the core and every module lying in modules/.
     */
    protected function migrateEverything(): void
    {
        $this->runMigrations($this->locator()->locate());
    }

    /**
     * @param string $source The core ("system") or the name of a module.
     * @param string ...$only Names of single migrations, without the version: all of them by default.
     */
    protected function migrate(string $source, string ...$only): void
    {
        $files = $this->locator()->locate($source);

        if ($only !== []) {
            $files = array_values(
                array_filter($files, static fn (MigrationFile $file): bool => in_array($file->name, $only, true))
            );
        }

        if ($files === []) {
            throw new RuntimeException(
                $only === []
                    ? sprintf('The source "%s" has no migrations.', $source)
                    : sprintf('The source "%s" has no migration named %s.', $source, implode(' or ', $only))
            );
        }

        $this->runMigrations($files);
    }

    private function locator(): MigrationLocator
    {
        return new MigrationLocator([
            new SystemMigrationSourceProvider(),
            new ModuleMigrationSourceProvider(),
        ]);
    }

    /**
     * @param list<MigrationFile> $files
     */
    private function runMigrations(array $files): void
    {
        $schema = new IlluminateSchema(Capsule::schema());
        $connection = new PdoConnection(Capsule::connection()->getPdo());

        foreach ($files as $file) {
            /** @psalm-suppress UnresolvableInclude */
            $migration = require $file->path;

            if (! $migration instanceof Migration) {
                throw new RuntimeException(sprintf('%s is not a migration.', $file->path));
            }

            $migration->bind($schema, $connection);
            $migration->up();
        }
    }
}
