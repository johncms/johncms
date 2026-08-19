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

/**
 * What this database has already been through.
 *
 * Kept in the database itself rather than in a file, so that it travels with the data it
 * describes: a restored dump brings back the journal that matches it, and a site moved to another
 * host does not run its converters a second time.
 */
interface MigrationRepositoryInterface
{
    /**
     * Creates the journal if it is not there yet. Called before writing, never before reading —
     * a database with no journal has simply been through nothing.
     */
    public function ensureStorageExists(): void;

    /**
     * @return list<AppliedMigration>
     */
    public function all(): array;

    /**
     * @return list<AppliedMigration>
     */
    public function forBatch(int $batch): array;

    public function lastBatch(): int;

    public function nextBatch(): int;

    public function log(MigrationFile $migration, int $batch, int $durationMs): void;

    public function forget(AppliedMigration $migration): void;
}
