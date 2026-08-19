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
 * A row of the journal: a migration this database has already been through.
 */
final readonly class AppliedMigration
{
    public function __construct(
        public string $source,
        public string $version,
        public string $name,
        public int $batch,
        public ?string $checksum,
        public string $appliedAt,
    ) {
    }

    public function id(): string
    {
        return $this->source . ':' . $this->version;
    }
}
