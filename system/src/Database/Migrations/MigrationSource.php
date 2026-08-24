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
 * Where a set of migrations comes from: the core, a module, or something a third party added.
 * The name is written into the journal, so it is the alias of the module — `forum`, never
 * `johncms/forum` — and must not change once its migrations have run anywhere.
 */
final readonly class MigrationSource
{
    public function __construct(
        public string $name,
        public string $directory,
    ) {
    }
}
