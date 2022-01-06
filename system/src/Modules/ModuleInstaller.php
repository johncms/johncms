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

use Illuminate\Support\Str;

class ModuleInstaller
{
    private string $module_name;
    protected Installer $installer;

    public function __construct(string $module_name)
    {
        $this->module_name = $module_name;

        $class_name = $this->getInstallerClassName();
        if (class_exists($class_name) && is_subclass_of($class_name, Installer::class)) {
            $this->installer = new $class_name($this->module_name);
        } else {
            throw new \RuntimeException('The ' . $class_name . ' class of the ' . $module_name . ' module installer was not found.');
        }
    }

    public function getInstaller(): Installer
    {
        return $this->installer;
    }

    protected function getInstallerClassName(): string
    {
        $namespace = Str::of($this->module_name)->explode('/')->map(function ($val) {
            return ucfirst($val);
        })->toArray();
        return '\\' . implode('\\', $namespace) . '\Install\Installer';
    }
}
