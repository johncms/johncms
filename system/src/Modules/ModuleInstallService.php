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

use Johncms\Database\Migrations\MigrationRunnerInterface;
use Johncms\Modules\Manifest\ModuleManifest;
use Throwable;

/**
 * The five things that can be done to a module, and the only place they are done.
 *
 * Files on disk are somebody else's business — Composer put them there, or an archive, or a person
 * over FTP. What happens here is everything else: the record that the site keeps, the migrations,
 * the hooks of the module, and the caches that would otherwise still describe the previous set of
 * modules.
 *
 * Uninstalling and removing files are deliberately separate. The module has to be asked to clean
 * up while it is still loaded — afterwards its classes are gone and there is nobody left to ask.
 */
final readonly class ModuleInstallService
{
    public function __construct(
        private ModuleRegistry $registry,
        private ModuleRepositoryInterface $modules,
        private ModuleStateStore $state,
        private MigrationRunnerInterface $migrator,
        private ModuleCacheInvalidator $cache,
        private ModuleAssetPublisher $assets,
        private ModuleCompatibilityChecker $compatibility = new ModuleCompatibilityChecker(),
    ) {
    }

    public function install(string $key, bool $withDemoData = false): ModuleOperationResult
    {
        $result = new ModuleOperationResult();

        $manifest = $this->modules->find($key);
        if ($manifest === null) {
            return $result->failed('find the module', sprintf('There is no module "%s" in the modules directory.', $key));
        }

        $record = $this->state->find($key);
        if ($record?->installed === true) {
            return $result->failed('install', sprintf('The module "%s" is already installed.', $key));
        }

        $problem = $this->refusalToLoad($manifest);
        if ($problem !== null) {
            return $result->failed('check the requirements', $problem);
        }

        $records = $this->state->all();
        $records[$key] = new ModuleStateRecord(
            key: $key,
            alias: $manifest->alias,
            version: $manifest->version,
            installedAt: time(),
        );

        try {
            $this->state->save($records);
            $this->registry->forget();
            $result->done('record the module', sprintf('alias "%s"', $manifest->alias));

            // The classes of the module have to be reachable before its migrations and its
            // installer run: the autoloader of the boot knew nothing about this module.
            ModuleAutoloader::instance()?->registerModule($manifest);

            $applied = $this->migrator->run($manifest->alias);
            $result->done('run the migrations', sprintf('%d applied', count($applied)));

            $this->installer($manifest)?->install();
            $result->done('run the installer of the module');

            $this->publishAssets($manifest, $result);

            if ($withDemoData) {
                $this->installer($manifest)?->installDemoData();
                $result->done('install the demo data');
            }
        } catch (Throwable $exception) {
            return $result->failed('install', $exception->getMessage());
        } finally {
            $this->cache->invalidate();
        }

        return $result;
    }

    public function enable(string $key): ModuleOperationResult
    {
        $result = new ModuleOperationResult();

        $record = $this->state->find($key);
        $manifest = $this->modules->find($key);

        if ($manifest === null || $record?->installed !== true) {
            return $result->failed('enable', sprintf('The module "%s" is not installed.', $key));
        }

        if ($record->enabled) {
            return $result->skipped('enable', sprintf('The module "%s" is already switched on.', $key));
        }

        $problem = $this->refusalToLoad($manifest);
        if ($problem !== null) {
            return $result->failed('check the requirements', $problem);
        }

        $this->write($key, $record->with(enabled: true));
        $result->done('switch the module on');

        $this->publishAssets($manifest, $result);

        return $result;
    }

    public function disable(string $key): ModuleOperationResult
    {
        $result = new ModuleOperationResult();

        $record = $this->state->find($key);
        $manifest = $this->modules->find($key);

        if ($manifest === null || $record?->installed !== true) {
            return $result->failed('disable', sprintf('The module "%s" is not installed.', $key));
        }

        if ($manifest->system) {
            return $result->failed('disable', sprintf('"%s" is a system module and cannot be switched off.', $key));
        }

        $dependents = $this->dependentsOf($key);
        if ($dependents !== []) {
            return $result->failed(
                'disable',
                sprintf('"%s" is needed by: %s. Switch those off first.', $key, implode(', ', $dependents))
            );
        }

        if (! $record->enabled) {
            return $result->skipped('disable', sprintf('The module "%s" is already switched off.', $key));
        }

        $this->write($key, $record->with(enabled: false));
        $result->done('switch the module off', 'its tables and data are untouched');

        $this->assets->unpublish($record->alias);
        $result->done('take its assets out of the document root');

        return $result;
    }

    /**
     * Run after the files of a module have been replaced with a newer version.
     */
    public function update(string $key): ModuleOperationResult
    {
        $result = new ModuleOperationResult();

        $manifest = $this->modules->find($key);
        $record = $this->state->find($key);

        if ($manifest === null || $record?->installed !== true) {
            return $result->failed('update', sprintf('The module "%s" is not installed.', $key));
        }

        if ($manifest->alias !== $record->alias) {
            return $result->failed(
                'update',
                sprintf(
                    'The module now calls itself "%s" and was installed as "%s". The alias names its'
                    . ' migrations and its templates and cannot change.',
                    $manifest->alias,
                    $record->alias
                )
            );
        }

        $problem = $this->refusalToLoad($manifest);
        if ($problem !== null) {
            return $result->failed('check the requirements', $problem);
        }

        $from = $record->version ?? CMS_VERSION;
        $to = $manifest->version ?? CMS_VERSION;

        try {
            $applied = $this->migrator->run($record->alias);
            $result->done('run the migrations', sprintf('%d applied', count($applied)));

            $this->installer($manifest)?->update($from, $to);
            $result->done('run the update hook', sprintf('%s -> %s', $from, $to));

            $this->publishAssets($manifest, $result);

            $this->write($key, $record->with(version: $manifest->version));
            $result->done('record the new version', $to);
        } catch (Throwable $exception) {
            return $result->failed('update', $exception->getMessage());
        } finally {
            $this->cache->invalidate();
        }

        return $result;
    }

    /**
     * Takes the module off this site. Its files stay where they are.
     *
     * @param bool $purge Also undo its migrations, which is what actually deletes its data.
     */
    public function uninstall(string $key, bool $purge = false): ModuleOperationResult
    {
        $result = new ModuleOperationResult();

        $manifest = $this->modules->find($key);
        $record = $this->state->find($key);

        if ($record?->installed !== true) {
            return $result->failed('uninstall', sprintf('The module "%s" is not installed.', $key));
        }

        if ($manifest?->system === true) {
            return $result->failed('uninstall', sprintf('"%s" is a system module and cannot be removed.', $key));
        }

        $dependents = $this->dependentsOf($key);
        if ($dependents !== []) {
            return $result->failed(
                'uninstall',
                sprintf('"%s" is needed by: %s. Remove those first.', $key, implode(', ', $dependents))
            );
        }

        // Refuse before anything happens rather than stop halfway: a module rolled back to the
        // middle of its own history leaves a schema nobody can describe.
        if ($purge) {
            $irreversible = $this->migrator->irreversible($record->alias);
            if ($irreversible !== []) {
                $names = array_map(static fn ($file): string => $file->name, $irreversible);

                return $result->failed(
                    'undo the migrations',
                    sprintf(
                        'The module does not say how to undo %s. Its data can only be removed by hand.',
                        implode(', ', $names)
                    )
                );
            }
        }

        try {
            $this->installer($manifest)?->uninstall();
            $result->done('run the uninstaller of the module');

            if ($purge) {
                $rolledBack = $this->migrator->rollback($record->alias, all: true);
                $result->done('undo the migrations', sprintf('%d rolled back', count($rolledBack)));
            } else {
                $result->skipped('undo the migrations', 'the tables and the data of the module are kept');
            }

            $this->assets->unpublish($record->alias);
            $result->done('take its assets out of the document root');

            $records = $this->state->all();
            unset($records[$key]);
            $this->state->save($records);
            $this->registry->forget();
            $result->done('forget the module');
        } catch (Throwable $exception) {
            return $result->failed('uninstall', $exception->getMessage());
        } finally {
            $this->cache->invalidate();
        }

        return $result;
    }

    /**
     * Copies what the module ships into the document root. A module without assets is not a
     * failure — most have none.
     */
    private function publishAssets(ModuleManifest $manifest, ModuleOperationResult $result): void
    {
        $published = $this->assets->publish($manifest);

        $published === 0
            ? $result->skipped('publish the assets', 'the module ships none')
            : $result->done('publish the assets', sprintf('%d files', $published));
    }

    /**
     * The installed modules that would stop working without this one.
     *
     * @return list<string>
     */
    private function dependentsOf(string $key): array
    {
        $installed = $this->registry->installed();

        return (new ModuleDependencyGraph($installed))->dependentsOf($key);
    }

    /**
     * Why this module may not be loaded here, if it may not.
     */
    private function refusalToLoad(ModuleManifest $manifest): ?string
    {
        foreach ($this->registry->states() as $state) {
            if ($state->key !== $manifest->key && $state->alias === $manifest->alias && $state->manifest !== null) {
                return sprintf('The alias "%s" is already held by the module "%s".', $manifest->alias, $state->key);
            }
        }

        $loaded = [];
        foreach ($this->registry->enabled() as $key => $enabled) {
            $record = $this->state->find($key);
            // The recorded version is what this site actually installed; the manifest may already
            // be a newer one lying on disk and waiting for module:update.
            $version = $record !== null ? $record->version : $enabled->version;
            $loaded[$key] = $version ?? CMS_VERSION;
        }

        return $this->compatibility->check($manifest, $loaded);
    }

    private function write(string $key, ModuleStateRecord $record): void
    {
        $records = $this->state->all();
        $records[$key] = $record;

        $this->state->save($records);
        $this->registry->forget();
        $this->cache->invalidate();
    }

    /**
     * The installer class of a module, if it ships one. Named by convention — the same convention
     * that has always applied: <Namespace>\Install\Installer.
     */
    private function installer(?ModuleManifest $manifest): ?Installer
    {
        if ($manifest === null) {
            return null;
        }

        foreach (array_keys($manifest->autoload->psr4) as $prefix) {
            $class = $prefix . 'Install\\Installer';
            if (is_a($class, Installer::class, true)) {
                return new $class($manifest->alias);
            }
        }

        $class = 'Johncms\\Modules\\' . ucfirst(basename($manifest->key)) . '\\Install\\Installer';

        return is_a($class, Installer::class, true) ? new $class($manifest->alias) : null;
    }
}
