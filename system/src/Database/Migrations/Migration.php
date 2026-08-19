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
use Johncms\Database\Migrations\Exceptions\IrreversibleMigrationException;
use Johncms\Database\Schema\SchemaInterface;

/**
 * One step the database is taken through, once, in order.
 *
 * A migration is a historical record, not application code: the one written today still has to
 * run, unchanged, on a site upgrading two years from now. That is the whole reason it is given a
 * schema and a connection and nothing else. Reaching for a model, a repository, a service or the
 * configuration ties it to code that will have moved on, and the failure surfaces on somebody
 * else's installation, years later, with no way back.
 *
 * The two rules that follow from it: a released migration is never edited — a mistake is
 * corrected by a new migration on top; and a migration never touches the tables of another
 * module.
 */
abstract class Migration
{
    protected SchemaInterface $schema;

    protected ConnectionInterface $db;

    /**
     * @internal Called by the migrator right after the file is loaded. Final so that a migration
     *           cannot take a constructor argument the migrator has no way of supplying.
     */
    final public function bind(SchemaInterface $schema, ConnectionInterface $db): void
    {
        $this->schema = $schema;
        $this->db = $db;
    }

    abstract public function up(): void;

    /**
     * Undoing the step, for development. Most migrations cannot: dropping the column that was
     * added throws away what was written into it. Saying nothing here refuses the rollback
     * instead of quietly destroying data.
     */
    public function down(): void
    {
        throw new IrreversibleMigrationException(
            sprintf('The migration %s does not say how to be rolled back.', static::class)
        );
    }

    /**
     * Whether to wrap the step in a transaction. Off by default, because MySQL does not roll back
     * DDL and the wrapper would only promise an atomicity it cannot deliver. A migration that
     * moves data and creates nothing may turn it on.
     */
    public function useTransaction(): bool
    {
        return false;
    }
}
