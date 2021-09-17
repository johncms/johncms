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
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;

class Migration
{
    protected Filesystem $filesystem;
    protected Migrator $migrator;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $connection = Manager::connection();
        $resolver = new ConnectionResolver(['default' => $connection]);
        $resolver->setDefaultConnection('default');
        $dbRepository = new DatabaseMigrationRepository($resolver, 'migrations');
        if (! $dbRepository->repositoryExists()) {
            $dbRepository->createRepository();
        }
        $this->migrator = new Migrator($dbRepository, $resolver, new Filesystem());
    }

    /**
     * @param string $name - migration name
     * @param string $moduleName - module name
     * @param string|null $table - table name
     * @param bool $create - true - migration to create a table, false - migration to update a table
     * @return string - migration path from document root.
     * @throws Exception
     */
    public function create(string $name, string $moduleName, ?string $table = null, bool $create = false): string
    {
        $migrationCreator = new MigrationCreator($this->filesystem, ROOT_PATH . 'system/stubs/migrations');
        $migrationPath = $migrationCreator->create($name, $this->getModuleMigrationsPath($moduleName), $table, $create);
        return mb_substr($migrationPath, mb_strlen(ROOT_PATH));
    }

    public function getModuleMigrationsPath(string $moduleName): string
    {
        if ($moduleName === 'system') {
            return ROOT_PATH . 'system/migrations';
        }
        return MODULES_PATH . $moduleName . '/migrations';
    }

    public function getAllModulesPaths(): array
    {
        $paths = [
            $this->getModuleMigrationsPath('system'),
        ];
        $moduleMigrationPaths = glob(MODULES_PATH . '*/*/migrations', GLOB_ONLYDIR);
        return array_merge($paths, $moduleMigrationPaths);
    }

    public function setMigratorOutput(OutputInterface $output): void
    {
        $this->migrator->setOutput($output);
    }

    /**
     * Run migrations
     *
     * @param string|null $moduleName
     * @param array $options
     * @return array
     */
    public function run(?string $moduleName = null, array $options = []): array
    {
        if (! $moduleName) {
            $paths = $this->getAllModulesPaths();
        } else {
            $paths = $this->getModuleMigrationsPath($moduleName);
        }
        return $this->migrator->run($paths, $options);
    }

    /**
     * Rollback migrations
     *
     * @param string|null $moduleName
     * @param array $options
     * @return array
     */
    public function rollback(?string $moduleName = null, array $options = []): array
    {
        if (! $moduleName) {
            $paths = $this->getAllModulesPaths();
        } else {
            $paths = $this->getModuleMigrationsPath($moduleName);
        }
        return $this->migrator->rollback($paths, $options);
    }
}
