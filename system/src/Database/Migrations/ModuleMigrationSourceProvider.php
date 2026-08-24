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

use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleRegistryFactory;

/**
 * The migrations of the modules, one source per module, named after the alias of the module —
 * `forum`, not `johncms/forum`. The name is what the journal records, and it must stay what it has
 * always been: the vendor level is a fact about where the files lie, not about the history the
 * database has been through.
 *
 * Every **installed** module is a source, the switched-off ones included. Their tables are still
 * in the database — switching a module off is not an uninstall — so their migrations are still
 * part of the schema of this site, and `migrate:status` has to say so. A module that was merely
 * dropped into the directory is not here: nothing of it has run, and it has nothing to say about
 * this database until it is installed.
 *
 * The order between modules is alphabetical, and it carries no meaning on purpose — a migration
 * of one module may not depend on the tables of another, so there is nothing for an order to
 * express. Determinism is all that is asked of it.
 */
final readonly class ModuleMigrationSourceProvider implements MigrationSourceProviderInterface
{
    public function __construct(private ?ModuleRegistry $registry = null)
    {
    }

    public function sources(): array
    {
        $modules = ($this->registry ?? ModuleRegistryFactory::registry())->installed();
        ksort($modules);

        $sources = [];
        foreach ($modules as $manifest) {
            $sources[] = new MigrationSource(
                $manifest->alias,
                $manifest->path . DIRECTORY_SEPARATOR . 'migrations'
            );
        }

        return $sources;
    }
}
