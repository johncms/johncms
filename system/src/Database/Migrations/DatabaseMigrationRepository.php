<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Migrations;

use Johncms\Database\ConnectionInterface;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Database\Schema\TableDefinition;

/**
 * The journal, in a table of its own.
 *
 * The table is created here rather than by a migration, because it is what tells migrations apart
 * from one another — it has to exist before the first one runs.
 */
final readonly class DatabaseMigrationRepository implements MigrationRepositoryInterface
{
    public const string TABLE = 'migrations';

    public function __construct(
        private SchemaInterface $schema,
        private ConnectionInterface $connection,
    ) {
    }

    public function ensureStorageExists(): void
    {
        if ($this->schema->hasTable(self::TABLE)) {
            return;
        }

        $this->schema->create(self::TABLE, static function (TableDefinition $table): void {
            $table->id();
            $table->string('source', 64);
            $table->string('version', 14);
            $table->string('name');
            $table->integer('batch')->unsigned()->index();
            // What the file held when it ran, so that editing it afterwards can be pointed out.
            $table->char('checksum', 40)->nullable();
            $table->dateTime('applied_at');
            $table->integer('duration_ms')->unsigned()->default(0);
            // Identity: the source and the version, never the file name.
            $table->unique(['source', 'version'], 'migrations_source_version_unique');
        });
    }

    public function all(): array
    {
        if (! $this->schema->hasTable(self::TABLE)) {
            return [];
        }

        $rows = $this->connection->select(
            'SELECT source, version, name, batch, checksum, applied_at FROM ' . self::TABLE . ' ORDER BY batch ASC, id ASC'
        );

        return array_map($this->toAppliedMigration(...), $rows);
    }

    public function forBatch(int $batch): array
    {
        if (! $this->schema->hasTable(self::TABLE)) {
            return [];
        }

        $rows = $this->connection->select(
            'SELECT source, version, name, batch, checksum, applied_at FROM ' . self::TABLE . ' WHERE batch = ? ORDER BY id ASC',
            [$batch]
        );

        return array_map($this->toAppliedMigration(...), $rows);
    }

    public function lastBatch(): int
    {
        if (! $this->schema->hasTable(self::TABLE)) {
            return 0;
        }

        $row = $this->connection->selectOne('SELECT MAX(batch) AS last_batch FROM ' . self::TABLE);

        return (int) ($row['last_batch'] ?? 0);
    }

    public function nextBatch(): int
    {
        return $this->lastBatch() + 1;
    }

    public function log(MigrationFile $migration, int $batch, int $durationMs): void
    {
        $this->connection->execute(
            'INSERT INTO ' . self::TABLE . ' (source, version, name, batch, checksum, applied_at, duration_ms)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $migration->source,
                $migration->version,
                $migration->name,
                $batch,
                $migration->checksum(),
                date('Y-m-d H:i:s'),
                $durationMs,
            ]
        );
    }

    public function forget(AppliedMigration $migration): void
    {
        $this->connection->execute(
            'DELETE FROM ' . self::TABLE . ' WHERE source = ? AND version = ?',
            [$migration->source, $migration->version]
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toAppliedMigration(array $row): AppliedMigration
    {
        $checksum = $row['checksum'] ?? null;

        return new AppliedMigration(
            source: (string) $row['source'],
            version: (string) $row['version'],
            name: (string) $row['name'],
            batch: (int) $row['batch'],
            checksum: $checksum === null ? null : (string) $checksum,
            appliedAt: (string) $row['applied_at'],
        );
    }
}
