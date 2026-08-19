<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Modules;

/**
 * What a module does at installation beyond its tables. The tables themselves come from its
 * migrations, so nothing here creates one.
 *
 * @psalm-consistent-constructor
 */
abstract class Installer
{
    /** @var string */
    protected $module_name = '';

    public function __construct(string $module_name)
    {
        $this->module_name = $module_name;
    }

    abstract public function uninstall(): void;

    /**
     * Installs the module's demo data.
     *
     * Does nothing by default. Modules that ship demo data override this method.
     */
    public function installDemoData(): void
    {
    }
}
