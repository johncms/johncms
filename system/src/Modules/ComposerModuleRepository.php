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

use Composer\InstalledVersions;
use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\Modules\Manifest\ModuleManifestLoader;
use Throwable;

/**
 * The modules Composer put in vendor/.
 *
 * A module installed with `composer require` is an ordinary package of type `johncms-module`, and
 * Composer keeps it where it keeps everything else. Nothing moves it into modules/: doing that
 * needs a Composer plugin, and the only one that could — installers-extender — is abandoned.
 * Asking the Composer runtime which packages of that type are installed costs nothing and depends
 * on nothing.
 *
 * A package whose manifest cannot be read is skipped rather than fatal. It is somebody else's
 * package on somebody else's site, and a site must not stop booting over it — the admin panel
 * lists what is wrong instead.
 */
final class ComposerModuleRepository implements ModuleRepositoryInterface
{
    public const string PACKAGE_TYPE = 'johncms-module';

    /** @var array<string, ModuleManifest>|null */
    private ?array $modules = null;

    public function __construct(private readonly ModuleManifestLoader $loader = new ModuleManifestLoader())
    {
    }

    public function all(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        if (! class_exists(InstalledVersions::class)) {
            return $this->modules = [];
        }

        $modules = [];
        foreach (InstalledVersions::getInstalledPackagesByType(self::PACKAGE_TYPE) as $package) {
            try {
                $path = InstalledVersions::getInstallPath($package);
            } catch (Throwable) {
                continue;
            }

            if ($path === null || ! is_file($path . DIRECTORY_SEPARATOR . ModuleManifestLoader::MANIFEST)) {
                continue;
            }

            try {
                $manifest = $this->loader->load($path);
            } catch (Throwable) {
                continue;
            }

            $modules[$manifest->key] = $manifest;
        }

        ksort($modules);

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
