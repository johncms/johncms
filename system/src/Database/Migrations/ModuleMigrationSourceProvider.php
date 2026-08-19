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
 * The migrations of the modules, one source per module, named after its directory.
 *
 * Modules are found on disk rather than read out of the installed list, because that is already
 * how the rest of the system finds them: the container loads the services of every module lying
 * in the directory, and the router its routes.
 *
 * The order between modules is alphabetical, and it carries no meaning on purpose — a migration
 * of one module may not depend on the tables of another, so there is nothing for an order to
 * express. Determinism is all that is asked of it.
 */
final readonly class ModuleMigrationSourceProvider implements MigrationSourceProviderInterface
{
    public function __construct(private string $modulesPath = MODULES_PATH)
    {
    }

    public function sources(): array
    {
        $directories = glob($this->modulesPath . '*', GLOB_ONLYDIR) ?: [];
        sort($directories);

        $sources = [];
        foreach ($directories as $directory) {
            $sources[] = new MigrationSource(basename($directory), $directory . DIRECTORY_SEPARATOR . 'migrations');
        }

        return $sources;
    }
}
