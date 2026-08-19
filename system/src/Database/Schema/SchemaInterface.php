<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Schema;

/**
 * Creating and altering tables, without naming the library that does it.
 *
 * Everything a migration is allowed to do to a schema goes through here, and everything here has
 * to be expressible on every database the CMS supports. That is what lets the layer underneath be
 * replaced — a different adapter behind this interface — without a single migration changing.
 */
interface SchemaInterface
{
    /**
     * @param callable(TableDefinition): void $definition
     */
    public function create(string $table, callable $definition): void;

    /**
     * @param callable(TableDefinition): void $definition
     */
    public function alter(string $table, callable $definition): void;

    public function drop(string $table): void;

    public function dropIfExists(string $table): void;

    public function rename(string $from, string $to): void;

    public function hasTable(string $table): bool;

    public function hasColumn(string $table, string $column): bool;

    public function hasIndex(string $table, string $index): bool;

    /**
     * Whether the table already has the named foreign key.
     *
     * A separate question from hasIndex(): a database is free to satisfy a foreign key with an
     * index that is already there, and then the constraint exists under a name no index carries.
     *
     * A database that does not name its foreign keys — SQLite is one — cannot answer this, and
     * says no. A migration guarded by it therefore repeats its work on such a database.
     */
    public function hasForeignKey(string $table, string $name): bool;

    /**
     * The type of a column as the database itself reports it, or null when there is no such
     * column. The spelling belongs to the database, so a caller compares loosely — with
     * str_contains(), not with an equality against a type name it made up.
     */
    public function getColumnType(string $table, string $column): ?string;

    /**
     * The length given to a string column declared without one.
     *
     * Exists for a single case: MySQL below 5.7 cannot index a 255-character utf8mb4 column, and
     * an installation on such a server has to cap it at 191.
     */
    public function setDefaultStringLength(int $length): void;
}
