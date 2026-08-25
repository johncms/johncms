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
 * Taking a database forward and back, as everything outside the migration machinery needs it.
 *
 * The Migrator itself is final and readonly — it holds a lock, a connection and a schema, and
 * nothing about it should be replaceable at runtime. What installs a module, however, depends on
 * migrations only through these three questions, and it says so through this.
 */
interface MigrationRunnerInterface
{
    /**
     * @return list<MigrationFile> What was applied, in the order it was applied.
     */
    public function run(?string $source = null, MigrationReporterInterface $reporter = new NullMigrationReporter()): array;

    /**
     * @return list<MigrationFile> What was rolled back, in the order it was rolled back.
     */
    public function rollback(
        ?string $source = null,
        int $steps = 1,
        MigrationReporterInterface $reporter = new NullMigrationReporter(),
        bool $all = false,
    ): array;

    /**
     * The applied migrations of a source that never said how to be undone.
     *
     * @return list<MigrationFile>
     */
    public function irreversible(string $source): array;
}
