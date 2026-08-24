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
 * How the classes of a module are found.
 *
 * A module shipped with the CMS declares nothing here: its namespace is in the root composer.json,
 * where Composer can build it into an optimised classmap. A module installed into a site cannot be
 * — the root composer.json is a file of the release, and rewriting it from the outside is how an
 * upgrade loses the list of what a site installed. Such a module says here what it needs, and the
 * autoloader of the modules adds it at boot.
 */
final readonly class ModuleAutoload
{
    /**
     * @param array<string, string> $psr4  Namespace prefix to a directory, relative to the module.
     * @param list<string>          $files Files to require, relative to the module. A module that
     *                                     brings dependencies of its own points at its own
     *                                     vendor/autoload.php here.
     */
    public function __construct(
        public array $psr4 = [],
        public array $files = [],
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->psr4 === [] && $this->files === [];
    }
}
