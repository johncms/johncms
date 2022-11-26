<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Modules;

use Composer\InstalledVersions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Johncms\Modules\Data\ModuleMetaData;
use Throwable;

class Modules
{
    protected static array $installedModules = [];

    /**
     * Get all installed modules
     *
     * @return string[]
     */
    public static function getInstalled(): array
    {
        if (! empty(self::$installedModules)) {
            return self::$installedModules;
        }
        $modulesDirectories = glob(MODULES_PATH . '*/*', GLOB_ONLYDIR);
        if ($modulesDirectories) {
            $moduleNames = array_map(fn($item) => Str::replaceFirst(MODULES_PATH, '', $item), $modulesDirectories);
            $installedModules = InstalledVersions::getInstalledPackages();
            return array_intersect($moduleNames, $installedModules);
        }
        return [];
    }

    /**
     * Get module version
     */
    public static function getModuleVersion(string $moduleName): ?string
    {
        try {
            return InstalledVersions::getPrettyVersion($moduleName);
        } catch (Throwable) {
            return null;
        }
    }

    public static function getRepoModuleVersion(string $moduleName)
    {
        try {
            $moduleData = file_get_contents('https://repo.packagist.org/p2/' . $moduleName . '.json');
            if ($moduleData) {
                $meta = json_decode($moduleData, true);
                return Arr::get($meta, 'packages.' . $moduleName . '.0.version');
            }
        } catch (Throwable) {
        }

        return null;
    }

    /**
     * Get module meta data
     */
    public static function getModuleData(string $moduleName): ?ModuleMetaData
    {
        $composerJsonPath = MODULES_PATH . $moduleName . '/composer.json';
        if (is_file($composerJsonPath)) {
            $composerConfig = file_get_contents($composerJsonPath);
            return ModuleMetaData::createFromComposerJson(json_decode($composerConfig, true));
        }
        return null;
    }

    /**
     * Get meta data for all modules
     *
     * @return array<string, ModuleMetaData|null>
     */
    public static function getModulesWithMetaData(): array
    {
        $modules = self::getInstalled();
        $modulesData = [];
        foreach ($modules as $module) {
            $modulesData[$module] = self::getModuleData($module);
        }
        return $modulesData;
    }

    /**
     * Required system modules
     *
     * @return string[]
     */
    public static function getSystemModules(): array
    {
        return [
            'johncms/admin',
            'johncms/auth',
        ];
    }

    /**
     * Check if a specific module is installed
     *
     * @param string $moduleName
     * @return bool
     */
    public static function isInstalled(string $moduleName): bool
    {
        return in_array($moduleName, self::getInstalled());
    }
}
