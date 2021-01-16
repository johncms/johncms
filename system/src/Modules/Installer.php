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

    abstract public function install(): void;

    abstract public function uninstall(): void;
}
