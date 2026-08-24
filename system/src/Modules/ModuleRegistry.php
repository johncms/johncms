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
        /** Loads the modules of the release only: the way back into a site a module has taken down. */
        private readonly bool $safeMode = false,
        private readonly ModuleCompatibilityChecker $compatibility = new ModuleCompatibilityChecker(),
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

        return $this->states = array_values($this->withCompatibility($this->heldBySystemModules($states)));
    }

    /**
     * Switches back on what a system module cannot do without.
     *
     * The admin panel is built against several modules of the release, so switching one of them
     * off in the configuration would take the panel down — and with it the only way to switch it
     * back on. Rather than obey into that corner, the registry keeps such a module loaded and says
     * in the listing who is holding it.
     *
     * It applies to installed modules only: something that was never installed cannot be held on,
     * and a system module needing it is a broken installation, reported by the pass below.
     *
     * @param array<string, ModuleState> $states
     * @return array<string, ModuleState>
     */
    private function heldBySystemModules(array $states): array
    {
        $holders = [];
        $queue = [];

        foreach ($states as $key => $state) {
            if ($state->system && $state->manifest !== null && $state->status !== ModuleStatus::Discovered) {
                $queue[] = $key;
            }
        }

        while ($queue !== []) {
            $current = array_shift($queue);
            $manifest = $states[$current]->manifest ?? null;

            if ($manifest === null) {
                continue;
            }

            foreach (array_keys($manifest->requires->modules) as $required) {
                if (isset($holders[$required]) || ! isset($states[$required])) {
                    continue;
                }

                $holders[$required] = $current;
                $queue[] = $required;
            }
        }

        foreach ($holders as $key => $holder) {
            $state = $states[$key];

            if ($state->status !== ModuleStatus::Disabled) {
                continue;
            }

            $states[$key] = new ModuleState(
                key: $state->key,
                alias: $state->alias,
                name: $state->name,
                status: ModuleStatus::Enabled,
                version: $state->version,
                system: $state->system,
                manifest: $state->manifest,
                problem: sprintf('Switched off in the configuration, but kept loaded: "%s" needs it.', $holder),
            );
        }

        return $states;
    }

    /**
     * Takes out the modules this site cannot run, and then the ones that needed them.
     *
     * The pass repeats until nothing changes, because requirements chain: a module built on the
     * forum stops being loadable the moment the forum does, and so does whatever was built on
     * that module. Doing it once would leave a module loaded against a dependency that is not
     * there — which is the container failing to compile, on a page nobody expected to break.
     *
     * @param array<string, ModuleState> $states
     * @return array<string, ModuleState>
     */
    private function withCompatibility(array $states): array
    {
        do {
            $loaded = [];
            foreach ($states as $key => $state) {
                if ($state->status === ModuleStatus::Enabled) {
                    $loaded[$key] = $state->version ?? CMS_VERSION;
                }
            }

            $refused = false;
            foreach ($states as $key => $state) {
                if ($state->status !== ModuleStatus::Enabled || $state->manifest === null) {
                    continue;
                }

                $problem = $this->compatibility->check($state->manifest, $loaded);
                if ($problem === null) {
                    continue;
                }

                $states[$key] = new ModuleState(
                    key: $state->key,
                    alias: $state->alias,
                    name: $state->name,
                    status: ModuleStatus::Incompatible,
                    version: $state->version,
                    system: $state->system,
                    manifest: $state->manifest,
                    problem: $problem,
                );
                $refused = true;
            }
        } while ($refused);

        return $states;
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

        $status = $this->statusOf($manifest, $record);

        // "system" means what it says: the module cannot be switched off. Obeying a configuration
        // that switches off the admin panel would leave nobody able to switch it back on, and the
        // half of the CMS built against it unable to compile.
        if ($manifest->system && $status === ModuleStatus::Disabled) {
            return new ModuleState(
                key: $manifest->key,
                alias: $alias,
                name: $manifest->name,
                status: ModuleStatus::Enabled,
                version: $version,
                system: true,
                manifest: $manifest,
                problem: 'Switched off in the configuration, but a system module cannot be switched off.',
            );
        }

        return new ModuleState(
            key: $manifest->key,
            alias: $alias,
            name: $manifest->name,
            status: $status,
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

        // Safe mode leaves the modules of the release and drops everything else. That is the
        // situation it exists for: a third-party module takes the site down and there is no way
        // into the admin panel to remove it. Leaving only the system modules would be no help —
        // the panel itself is built against several modules of the release.
        if ($this->safeMode && ! in_array($manifest->key, $this->bundled, true)) {
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
