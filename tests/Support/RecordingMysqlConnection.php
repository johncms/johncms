<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\MySqlConnection;
use RuntimeException;

/**
 * A MySQL connection that writes nothing down and answers nothing back: every statement handed to
 * it is recorded instead of executed, and every question about what the database already contains
 * is answered as an empty database.
 *
 * That makes it a way of asking "what DDL would this produce on MySQL, on a fresh installation"
 * without a server, which is how the schema of the CMS is pinned: the statements the migrations
 * produce are compared against the ones recorded from the code that used to build it.
 *
 * The closure standing in for the PDO throws, so anything that genuinely needed a server fails
 * loudly instead of quietly needing one.
 */
final class RecordingMysqlConnection extends MySqlConnection
{
    /** @var list<string> */
    private array $statements = [];

    public static function create(): self
    {
        $connection = new self(
            static fn () => throw new RuntimeException('Building the schema must not need a database server.'),
            'johncms',
            '',
            [
                'driver'    => 'mysql',
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'engine'    => 'InnoDB ROW_FORMAT=DYNAMIC',
            ]
        );

        $connection->useDefaultSchemaGrammar();

        return $connection;
    }

    /**
     * The statements handed to it, in the order they were handed over.
     *
     * @return list<string>
     */
    public function statements(): array
    {
        return $this->statements;
    }

    /**
     * The same statements, gathered under the table each one is about.
     *
     * Comparing them table by table rather than as one long list is what lets the migrations be
     * cut up differently from the code they replace: what has to match is the table that comes
     * out, not the order the tables were built in.
     *
     * @return array<string, list<string>>
     */
    public function statementsByTable(): array
    {
        $byTable = [];

        foreach ($this->statements as $statement) {
            preg_match('/^(?:create table|alter table) `([^`]+)`/i', $statement, $matches);
            $table = $matches[1] ?? '_other';

            $byTable[$table][] = $statement;
        }

        ksort($byTable);

        return $byTable;
    }

    public function isMaria(): bool
    {
        return false;
    }

    public function getServerVersion(): string
    {
        return '8.0.36';
    }

    public function statement($query, $bindings = []): bool
    {
        $this->statements[] = $query;

        return true;
    }

    public function select($query, $bindings = [], $useReadPdo = true): array
    {
        return [];
    }

    public function selectFromWriteConnection($query, $bindings = []): array
    {
        return [];
    }

    public function scalar($query, $bindings = [], $useReadPdo = true): mixed
    {
        return null;
    }
}
