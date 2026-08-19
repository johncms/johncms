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
 * Answers where migrations are to be looked for.
 *
 * The extension point of the whole mechanism: a package that ships tables of its own implements
 * this, the container tags it, and its migrations join the run without a line changed in the
 * core. Providers are asked in the order they are tagged, and that order is the order the
 * sources run in — the core before everything else, because a module may point at its tables
 * and it may not point at theirs.
 */
interface MigrationSourceProviderInterface
{
    /**
     * @return list<MigrationSource>
     */
    public function sources(): array;
}
