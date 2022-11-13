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
use Illuminate\Support\Str;

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
