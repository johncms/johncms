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

use Johncms\Modules\Manifest\ModuleManifest;

/**
 * One module as the registry sees it: what it is, and what the site has done with it.
 *
 * This is the reporting shape — what `module:list` prints and what the admin panel will show.
 * The loading path asks the registry for manifests instead.
 */
final readonly class ModuleState
{
    public function __construct(
        public string $key,
        public string $alias,
        public string $name,
        public ModuleStatus $status,
        public ?string $version = null,
        public bool $system = false,
        /** Null when the files are gone: there is nothing to read a manifest from. */
        public ?ModuleManifest $manifest = null,
        /** Why a module is broken, in a sentence a person can act on. */
        public ?string $problem = null,
    ) {
    }
}
