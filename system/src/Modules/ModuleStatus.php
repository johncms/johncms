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
 * What the system currently makes of a module. Not a property of the module — a comparison of
 * what lies on disk with what the site has recorded about it.
 */
enum ModuleStatus: string
{
    /** Installed and loaded: its services, routes, templates and translations are part of the site. */
    case Enabled = 'enabled';

    /** Installed and not loaded. Its tables and data are untouched — this is not an uninstall. */
    case Disabled = 'disabled';

    /** Lying in modules/ and never installed. Nothing of it is loaded. */
    case Discovered = 'discovered';

    /**
     * Its installation was begun and did not finish: the record is there so its migrations can be
     * found, and nothing of it is loaded until they have run through. Installing it again is what
     * finishes the job.
     */
    case Installing = 'installing';

    /** Recorded as installed, but unusable: the files are gone, or something makes it unloadable. */
    case Broken = 'broken';

    /** Installed, but this site cannot run it: the version of PHP, of the CMS, or a module it needs. */
    case Incompatible = 'incompatible';
}
