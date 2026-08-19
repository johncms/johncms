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
 * One line of what migrate:status shows. Flat on purpose: it also has to describe a migration
 * the journal remembers and the disk no longer has.
 */
final readonly class MigrationStatus
{
    public function __construct(
        public string $source,
        public string $version,
        public string $name,
        public bool $isApplied,
        public bool $fileExists,
        public ?int $batch = null,
        public ?string $appliedAt = null,
        public bool $checksumMatches = true,
    ) {
    }
}
