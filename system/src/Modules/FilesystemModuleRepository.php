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
use Johncms\Modules\Manifest\ModuleManifestLoader;

/**
 * Reads the modules from modules/, two levels deep: a vendor directory, and a module inside it.
 *
 * A directory without a module.php is not a module and is passed over — that is what an unpacked
 * archive, a leftover of an older layout or a stray copy looks like, and none of them may stop
 * the site from booting. A manifest that exists but cannot be read is a different matter: it is
 * an error of the module, and it is raised.
 */
final class FilesystemModuleRepository implements ModuleRepositoryInterface
{
    /** @var array<string, ModuleManifest>|null */
    private ?array $modules = null;

    public function __construct(
        private readonly ModuleManifestLoader $loader = new ModuleManifestLoader(),
        private readonly string $modulesPath = MODULES_PATH,
    ) {
    }

    public function all(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        $directories = (array) glob($this->modulesPath . '*' . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
        sort($directories);

        $modules = [];
        foreach ($directories as $directory) {
            $directory = (string) $directory;
            if (! is_file($directory . DIRECTORY_SEPARATOR . ModuleManifestLoader::MANIFEST)) {
                continue;
            }

            $manifest = $this->loader->load($directory);
            $modules[$manifest->key] = $manifest;
        }

        return $this->modules = $modules;
    }

    public function find(string $key): ?ModuleManifest
    {
        return $this->all()[$key] ?? null;
    }

    public function forget(): void
    {
        $this->modules = null;
    }
}
