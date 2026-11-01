<?php

declare(strict_types=1);

namespace Johncms\Modules;

use Johncms\Modules\Manifest\ModuleManifest;

final class ModuleInstallerFactory
{
    public function create(ModuleManifest $manifest): ?Installer
    {
        foreach ($manifest->namespaces() as $prefix) {
            $class = $prefix . 'Install\\Installer';
            if (is_a($class, Installer::class, true)) {
                return new $class($manifest->alias);
            }
        }

        $class = 'Johncms\\Modules\\' . ucfirst(basename($manifest->key)) . '\\Install\\Installer';

        return is_a($class, Installer::class, true) ? new $class($manifest->alias) : null;
    }
}
