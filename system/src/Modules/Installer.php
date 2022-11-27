<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Modules;

abstract class Installer
{
    public function __construct(
        protected string $moduleName
    ) {
    }

    abstract public function install(): void;

    abstract public function uninstall(): void;

    public function afterInstall(): void
    {
    }

    public function afterUpdate(): void
    {
    }
}
