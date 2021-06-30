<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database;

use Exception;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Filesystem\Filesystem;

class Migration
{
    public function __construct()
    {
    }

    /**
     * @param string $name - migration name
     * @param string $module_name - module name
     * @param string|null $table - table name
     * @param bool $create - true - migration to create a table, false - migration to update a table
     * @return string - migration path from document root.
     * @throws Exception
     */
    public function create(string $name, string $module_name, ?string $table = null, bool $create = false): string
    {
        $migration_creator = new MigrationCreator(new Filesystem(), ROOT_PATH . 'system/stubs/migrations');
        $migration_path = $migration_creator->create($name, $this->getModuleMigrationsPath($module_name), $table, $create);
        return mb_substr($migration_path, mb_strlen(ROOT_PATH));
    }

    public function getModuleMigrationsPath(string $module_name): string
    {
        if ($module_name === 'system') {
            return ROOT_PATH . 'system/migrations';
        }
        return MODULES_PATH . $module_name . '/migrations';
    }
}
