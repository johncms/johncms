<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules;

use Johncms\Modules\Manifest\ModuleManifest;

/**
 * Which modules this site loads, and what it makes of the ones it does not.
 *
 * Three inputs: what lies on disk (the repository), what ships with this release (the bundled
 * list of modules.global.php) and what the site has done since (modules.local.php). A module is
 * loaded when it is on disk and either belongs to the release and was not switched off, or was
 * installed here and is switched on.
 *
 * Nothing here raises. A module recorded as installed whose files are gone, and a module whose
 * alias another one already holds, are reported as broken and left out of the loading — a site
 * must not be taken down by what happened to a directory. Installing is where such a thing is an
 * error worth refusing.
 */
final class ModuleRegistry
{
    /** @var list<ModuleState>|null */
    private ?array $states = null;

    /**
     * @param list<string> $bundled Keys of the modules shipped with this release.
     */
    public function __construct(
        private readonly ModuleRepositoryInterface $modules,
        private readonly ModuleStateStore $state,
        private readonly array $bundled = [],
        /** Loads system modules only: the way back into a site a module has taken down. */
        private readonly bool $safeMode = false,
    ) {
    }

    /**
     * The modules to load: services, routes, templates, translations.
     *
     * @return array<string, ModuleManifest>
     */
    public function enabled(): array
    {
        $enabled = [];
        foreach ($this->states() as $state) {
            if ($state->status !== ModuleStatus::Enabled || $state->manifest === null) {
                continue;
            }

            $enabled[$state->key] = $state->manifest;
        }

        return $enabled;
    }

    /**
     * Everything installed here, switched off included. Their tables exist, so the migrations of a
     * disabled module are still part of the schema of this site.
     *
     * @return array<string, ModuleManifest>
     */
    public function installed(): array
    {
        $installed = [];
        foreach ($this->states() as $state) {
            if ($state->manifest === null) {
                continue;
            }

            if ($state->status === ModuleStatus::Enabled || $state->status === ModuleStatus::Disabled) {
                $installed[$state->key] = $state->manifest;
            }
        }

        return $installed;
    }

    /**
     * Every module this site knows of, whatever state it is in. The reporting view.
     *
     * @return list<ModuleState>
     */
    public function states(): array
    {
        if ($this->states !== null) {
            return $this->states;
        }

        $onDisk = $this->modules->all();
        ksort($onDisk);

        $records = $this->state->all();

        // Installed modules claim their alias first: a module dropped into the directory must not
        // be able to take the namespace of one the site is already running.
        $claimed = [];
        $states = [];

        foreach ([true, false] as $installedPass) {
            foreach ($onDisk as $key => $manifest) {
                if ($this->isInstalled($key, $records) !== $installedPass) {
                    continue;
                }

                $states[$key] = $this->stateOf($manifest, $records[$key] ?? null, $claimed);
            }
        }

        // Recorded, but no longer on disk: the files were deleted without the module being
        // uninstalled, and its tables are still there.
        foreach ($records as $key => $record) {
            if (isset($states[$key]) || ! $record->installed) {
                continue;
            }

            $states[$key] = new ModuleState(
                key: $key,
                alias: $record->alias,
                name: basename($key),
                status: ModuleStatus::Broken,
                version: $record->version,
                problem: 'Recorded as installed, but there is no such module in the modules directory.',
            );
        }

        ksort($states);

        return $this->states = array_values($states);
    }

    public function find(string $key): ?ModuleState
    {
        foreach ($this->states() as $state) {
            if ($state->key === $key) {
                return $state;
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $claimed Alias to the key holding it, by reference.
     */
    private function stateOf(ModuleManifest $manifest, ?ModuleStateRecord $record, array &$claimed): ModuleState
    {
        $alias = $record !== null ? $record->alias : $manifest->alias;
        $version = $record !== null ? $record->version : $manifest->version;

        if (isset($claimed[$alias])) {
            return new ModuleState(
                key: $manifest->key,
                alias: $alias,
                name: $manifest->name,
                status: ModuleStatus::Broken,
                version: $version,
                system: $manifest->system,
                manifest: $manifest,
                problem: sprintf('The alias "%s" is already held by the module "%s".', $alias, $claimed[$alias]),
            );
        }

        $claimed[$alias] = $manifest->key;

        return new ModuleState(
            key: $manifest->key,
            alias: $alias,
            name: $manifest->name,
            status: $this->statusOf($manifest, $record),
            version: $version,
            system: $manifest->system,
            manifest: $manifest,
        );
    }

    private function statusOf(ModuleManifest $manifest, ?ModuleStateRecord $record): ModuleStatus
    {
        if (! $this->isInstalled($manifest->key, $record === null ? [] : [$manifest->key => $record])) {
            return ModuleStatus::Discovered;
        }

        // Safe mode leaves the system modules and nothing else: enough to reach the admin panel
        // and switch off whatever is at fault.
        if ($this->safeMode && ! $manifest->system) {
            return ModuleStatus::Disabled;
        }

        return $record === null || $record->enabled ? ModuleStatus::Enabled : ModuleStatus::Disabled;
    }

    /**
     * A module of this release counts as installed unless the site says otherwise: the installer
     * of a fresh site puts them all in, and an upgrade brings their migrations with it.
     *
     * @param array<string, ModuleStateRecord> $records
     */
    private function isInstalled(string $key, array $records): bool
    {
        $record = $records[$key] ?? null;

        if ($record !== null) {
            return $record->installed;
        }

        return in_array($key, $this->bundled, true);
    }
}
