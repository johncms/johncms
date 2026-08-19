<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Migrations;

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Migrations\MigrationLocator;
use Johncms\Database\Migrations\ModuleMigrationSourceProvider;
use Johncms\Database\Migrations\SystemMigrationSourceProvider;
use Johncms\Database\PdoConnection;
use Johncms\Database\Schema\Adapters\IlluminateSchema;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\RecordingMysqlConnection;

/**
 * The baseline migrations against the schema they replaced.
 *
 * The fixture holds the exact MySQL the installer and the module installers produced on a fresh
 * database, recorded from that code before it was deleted. The migrations have to produce the
 * same, table by table: a site installed today and a site upgraded from 9.9 have to end up with
 * the same tables, and the baseline skips whatever already exists — so a column that came out
 * differently here would never be corrected on the upgraded site, and the two would drift apart
 * with nothing to report it.
 *
 * The statements of a table are compared as a set rather than as a sequence. A key declared on a
 * column lands at a different point in the sequence than it used to — the schema builder of
 * Eloquent gathers those after the keys declared on the table, while a table description here
 * keeps the order it was written in — and the table that comes out is the same either way. What
 * would not be the same, and is what this test is for, is a statement that changed or went
 * missing.
 */
final class BaselineSchemaParityTest extends TestCase
{
    private const string FIXTURE = ROOT_PATH . 'tests/fixtures/schema/baseline-mysql.json';

    #[DataProvider('sources')]
    public function testTheMigrationsOfASourceBuildTheSchemaTheyReplaced(string $source): void
    {
        self::assertSame(
            $this->sorted(self::fixture()[$source]),
            $this->sorted($this->schemaBuiltBy($source))
        );
    }

    /**
     * Every source that used to create tables still does, and no source has quietly appeared.
     */
    public function testTheSourcesAreTheOnesThatUsedToCreateTables(): void
    {
        $withTables = [];

        foreach ($this->locator()->sources() as $source) {
            if ($this->locator()->locate($source->name) !== []) {
                $withTables[] = $source->name;
            }
        }

        sort($withTables);
        $expected = array_keys(self::fixture());
        sort($expected);

        self::assertSame($expected, $withTables);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function sources(): iterable
    {
        foreach (array_keys(self::fixture()) as $source) {
            yield $source => [$source];
        }
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private static function fixture(): array
    {
        /** @var array<string, array<string, list<string>>> $decoded */
        $decoded = json_decode((string) file_get_contents(self::FIXTURE), true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /**
     * @return array<string, list<string>>
     */
    private function schemaBuiltBy(string $source): array
    {
        $recorder = RecordingMysqlConnection::create();
        $schema = new IlluminateSchema($recorder->getSchemaBuilder());
        // The baseline moves no data, so nothing reaches this; it is here because binding a
        // migration asks for a connection.
        $connection = new PdoConnection(new PDO('sqlite::memory:'));

        foreach ($this->locator()->locate($source) as $file) {
            $migration = require $file->path;

            self::assertInstanceOf(Migration::class, $migration, $file->path);

            $migration->bind($schema, $connection);
            $migration->up();
        }

        return $recorder->statementsByTable();
    }

    /**
     * @param array<string, list<string>> $byTable
     * @return array<string, list<string>>
     */
    private function sorted(array $byTable): array
    {
        foreach ($byTable as &$statements) {
            $statements = array_map($this->normalize(...), $statements);
            sort($statements);
        }

        ksort($byTable);

        return $byTable;
    }

    /**
     * The word indexes are the one thing the old code wrote by hand, in upper case and with a
     * space before the columns, while the schema builder writes the same statement its own way.
     * Only those are brought to one spelling, and everything that decides what the index is — the
     * table, its name, the columns it covers — is still compared as it stands.
     */
    private function normalize(string $statement): string
    {
        if (stripos($statement, 'fulltext') === false) {
            return $statement;
        }

        return str_replace('` (', '`(', strtolower($statement));
    }

    private function locator(): MigrationLocator
    {
        return new MigrationLocator([
            new SystemMigrationSourceProvider(),
            new ModuleMigrationSourceProvider(),
        ]);
    }
}
