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
 * For the callers that only want the result: the installer, a test.
 */
final readonly class NullMigrationReporter implements MigrationReporterInterface
{
    public function starting(MigrationFile $migration, MigrationDirection $direction): void
    {
    }

    public function finished(MigrationFile $migration, MigrationDirection $direction, int $durationMs): void
    {
    }

    public function failed(MigrationFile $migration, MigrationDirection $direction, Throwable $exception): void
    {
    }
}
