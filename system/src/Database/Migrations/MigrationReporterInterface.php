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

use Throwable;

/**
 * Where the progress of a run is told. The console prints it, the maintenance page collects it,
 * a test counts it — the migrator itself says nothing on its own.
 */
interface MigrationReporterInterface
{
    public function starting(MigrationFile $migration, MigrationDirection $direction): void;

    public function finished(MigrationFile $migration, MigrationDirection $direction, int $durationMs): void;

    public function failed(MigrationFile $migration, MigrationDirection $direction, Throwable $exception): void;
}
