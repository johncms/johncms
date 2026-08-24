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

use Composer\Autoload\ClassLoader;

/**
 * Makes the classes of the installed modules loadable.
 *
 * Runs at boot, before the container is compiled — the container is built out of the classes of
 * these modules, so they have to be findable first.
 *
 * The prefixes are added to the class loader of Composer rather than to an autoloader of our own:
 * it is already registered, it already resolves PSR-4, and one loader means one order of
 * resolution. Modules of the release are not here at all — their namespaces are in the root
 * composer.json, where an optimised classmap can cover them.
 *
 * A module is loaded from the registry, so a module that is switched off keeps its classes
 * unreachable — which is what makes switching one off mean anything.
 */
final readonly class ModuleAutoloader
{
    public function __construct(
        private ClassLoader $loader,
        private ModuleRegistry $registry,
    ) {
    }

    public function register(): void
    {
        foreach ($this->registry->enabled() as $manifest) {
            if ($manifest->autoload->isEmpty()) {
                continue;
            }

            foreach ($manifest->autoload->psr4 as $prefix => $directory) {
                $this->loader->addPsr4($prefix, $manifest->path . DIRECTORY_SEPARATOR . $directory);
            }

            foreach ($manifest->autoload->files as $file) {
                $path = $manifest->path . DIRECTORY_SEPARATOR . $file;

                // A module pointing at a vendor/autoload.php it did not ship is a broken package,
                // not a reason to stop the site: everything else it declares still works, and the
                // missing classes surface where they are actually used.
                if (is_file($path)) {
                    require_once $path;
                }
            }
        }
    }
}
