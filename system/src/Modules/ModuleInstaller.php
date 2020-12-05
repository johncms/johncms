<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Modules;

class ModuleInstaller
{
    /** @var string */
    private $module_name;

    /** @var Installer */
    protected $installer;

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

    public function install(): void
    {
        $this->installer->install();
    }

    public function uninstall(): void
    {
        $this->installer->uninstall();
    }

    protected function getInstallerClassName(): string
    {
        return '\\' . ucfirst($this->module_name) . '\Install\Installer';
    }
}
