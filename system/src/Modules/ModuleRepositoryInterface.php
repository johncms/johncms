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
 * What lies on disk, whatever the site has done with it. Whether a module is installed, switched
 * off or merely dropped into the directory is a question for the registry; this answers only what
 * is there.
 */
interface ModuleRepositoryInterface
{
    /**
     * Every module found on disk, keyed by its key.
     *
     * @return array<string, ModuleManifest>
     */
    public function all(): array;

    public function find(string $key): ?ModuleManifest;
}
