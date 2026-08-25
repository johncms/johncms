<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Manifest;

/**
 * A module as its manifest describes it.
 *
 * Two identifiers, and confusing them is the mistake to avoid. The **key** is `vendor/name`: the
 * directory the module lies in, the name of its Composer package, and what the registry, the CLI
 * and the dependencies of other modules refer to. The **alias** is flat — `forum`, `vasya.blog` —
 * and is what everything that cannot hold a slash uses: the Twig namespace, the gettext domain
 * and the source of its migrations.
 *
 * The alias is fixed once a module is released. The journal of migrations is written under it, so
 * changing it would cut a module off from its own history.
 */
final readonly class ModuleManifest
{
    public function __construct(
        public string $key,
        public string $alias,
        /** Absolute path of the module directory, without a trailing separator. */
        public string $path,
        /** What to call the module in an interface. */
        public string $name,
        /**
         * Null for a module shipped with the CMS: its version is the version of the CMS, and such
         * a module is never out of date on its own.
         */
        public ?string $version = null,
        /** A system module cannot be switched off or removed. */
        public bool $system = false,
        /** Empty for a module of the release: its namespace lives in the root composer.json. */
        public ModuleAutoload $autoload = new ModuleAutoload(),
        public ModuleRequirements $requires = new ModuleRequirements(),
        public ModuleAssets $assets = new ModuleAssets(),
    ) {
    }
}
