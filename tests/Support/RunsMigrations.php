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
 * A test may ask for single migrations of a source instead of all of them, and the tables of the
 * core are a case where it has to. Their index names come from 9.x, where several tables name an
 * index `user_id` — MySQL keeps index names per table, SQLite keeps them per database, so the
 * whole core baseline cannot be built on the database this suite runs on. Asking for the part
 * that is needed also says what the test depends on.
 *
 * Use together with BootsInMemoryDatabase, after bootDatabase().
 */
trait RunsMigrations
{
    /**
     * @param string $source The core ("system") or the name of a module.
     * @param string ...$only Names of single migrations, without the version: all of them by default.
     */
    protected function migrate(string $source, string ...$only): void
    {
        $schema = new IlluminateSchema(Capsule::schema());
        $connection = new PdoConnection(Capsule::connection()->getPdo());

        $locator = new MigrationLocator([
            new SystemMigrationSourceProvider(),
            new ModuleMigrationSourceProvider(),
        ]);

        $files = $locator->locate($source);

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
