<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authorization;

use Johncms\Modules\Manifest\ModuleManifest;
use ReflectionClass;

/**
 * Takes the permissions a module declared out of the roles that were granted them.
 *
 * Which permissions those are is asked of the module itself — the providers it registered are
 * still in the container while it is being uninstalled, and they are the only authority on what it
 * declared. Matching by the prefix of a key would be a guess: `admin.settings.manage` belongs to
 * the core, not to the module whose alias happens to be `admin`.
 *
 * A copy of what is about to be removed is written out first. Permissions are configuration that
 * somebody spent an evening on, and a module removed by mistake should not cost them that.
 */
final readonly class RolePermissionPurger
{
    /**
     * @param iterable<PermissionProviderInterface> $providers
     */
    public function __construct(
        private iterable $providers,
        private RoleRepositoryInterface $roles,
        private string $backupPath = DATA_PATH . 'backups',
    ) {
    }

    /**
     * The permission keys this module declares.
     *
     * @return list<string>
     */
    public function keysOf(ModuleManifest $manifest): array
    {
        $namespaces = $manifest->namespaces();

        $keys = [];
        foreach ($this->providers as $provider) {
            $class = (new ReflectionClass($provider))->getName();

            foreach ($namespaces as $namespace) {
                if (! str_starts_with($class, $namespace)) {
                    continue;
                }

                foreach ($provider->permissions() as $definition) {
                    $keys[] = $definition->key;
                }

                break;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return int How many grants were taken away, across all roles.
     */
    public function purge(ModuleManifest $manifest): int
    {
        $keys = $this->keysOf($manifest);

        if ($keys === []) {
            return 0;
        }

        $removed = [];
        foreach ($this->roles->all() as $role) {
            $held = $this->roles->permissionsFor([$role->id]);
            $keep = array_values(array_diff($held, $keys));

            if (count($keep) === count($held)) {
                continue;
            }

            $removed[$role->slug] = array_values(array_intersect($held, $keys));
            $this->roles->setPermissions($role->id, $keep);
        }

        if ($removed === []) {
            return 0;
        }

        $this->backup($manifest, $removed);

        return array_sum(array_map(count(...), $removed));
    }

    /**
     * @param array<string, list<string>> $removed
     */
    private function backup(ModuleManifest $manifest, array $removed): void
    {
        if (! is_dir($this->backupPath) && ! mkdir($this->backupPath, 0o755, true)) {
            return;
        }

        $file = sprintf(
            '%s%spermissions-%s-%s.json',
            rtrim($this->backupPath, DIRECTORY_SEPARATOR),
            DIRECTORY_SEPARATOR,
            str_replace('/', '-', $manifest->key),
            date('Y-m-d-His')
        );

        @file_put_contents($file, json_encode($removed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
