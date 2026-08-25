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

/**
 * What a module does around its tables. The tables themselves come from its migrations, and
 * nothing here creates one — that rule does not change: a migration is a historical record that
 * has to run unchanged years from now, while this is ordinary application code.
 *
 * Every method does nothing by default. A module overrides what it needs and leaves the rest.
 *
 * All of them must be safe to run twice. An installation that failed halfway is run again, and
 * "already there" is a normal state to meet, not an error.
 *
 * @psalm-consistent-constructor
 */
abstract class Installer
{
    public function __construct(protected string $moduleName = '')
    {
    }

    /**
     * Settings, reference data, directories — whatever the module needs to work that a migration
     * has no business creating.
     */
    public function install(): void
    {
    }

    /**
     * A one-off step between two versions of the module, run after its migrations.
     *
     * @param string $from The version that was installed, or the version of the CMS for a module
     *                     of the release.
     */
    public function update(string $from, string $to): void
    {
    }

    /**
     * What the migrations of the module cannot take back: uploaded files, rows it wrote into
     * tables belonging to the core, settings of its own.
     *
     * Runs while the module is still loaded — after its files are gone there is nothing left to
     * ask.
     */
    public function uninstall(): void
    {
    }

    /**
     * Installs the module's demo data.
     */
    public function installDemoData(): void
    {
    }
}
