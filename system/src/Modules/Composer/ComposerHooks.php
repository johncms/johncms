<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Composer;

use Composer\Installer\PackageEvent;
use Composer\Package\PackageInterface;

/**
 * What Composer says after it has moved the files of a module.
 *
 * It says something and writes a note. It does not install anything, and that is the whole design
 * decision here: the previous attempt at this booted the entire application inside the Composer
 * process and ran migrations from there, which meant `composer install` on a developer machine
 * migrating whatever database the configuration happened to point at, and an error in a migration
 * failing Composer rather than the installation.
 *
 * So the files arrive, and the site is told about them the next time somebody looks: by this
 * message in the terminal, and by the modules section of the admin panel, which lists what lies
 * on disk and was never installed.
 */
final class ComposerHooks
{
    private const string PACKAGE_TYPE = 'johncms-module';

    /** @noinspection PhpUnused — referenced from composer.json */
    public static function afterInstall(PackageEvent $event): void
    {
        $package = self::moduleOf($event);

        if ($package === null) {
            return;
        }

        $event->getIO()->write(sprintf(
            '<info>%s</info> is now on disk. Finish with: <comment>php system/bin/console module:install %s</comment>',
            $package->getPrettyName(),
            $package->getPrettyName()
        ));
    }

    /** @noinspection PhpUnused — referenced from composer.json */
    public static function afterUpdate(PackageEvent $event): void
    {
        $package = self::moduleOf($event);

        if ($package === null) {
            return;
        }

        $event->getIO()->write(sprintf(
            '<info>%s</info> was updated. Run: <comment>php system/bin/console module:update %s</comment>',
            $package->getPrettyName(),
            $package->getPrettyName()
        ));
    }

    /**
     * Removing the files is not uninstalling the module: its tables, its data and the permissions
     * granted for it are still there, and the code that knows how to take them back is in the
     * package about to be deleted.
     *
     * @noinspection PhpUnused — referenced from composer.json
     */
    public static function beforeUninstall(PackageEvent $event): void
    {
        $package = self::moduleOf($event);

        if ($package === null) {
            return;
        }

        $event->getIO()->write(sprintf(
            '<warning>%s</warning> is about to be deleted, but its tables and data stay.'
            . ' Undo it properly first: <comment>php system/bin/console module:uninstall %s --purge</comment>',
            $package->getPrettyName(),
            $package->getPrettyName()
        ));
    }

    private static function moduleOf(PackageEvent $event): ?PackageInterface
    {
        $operation = $event->getOperation();

        $package = match (true) {
            method_exists($operation, 'getPackage')       => $operation->getPackage(),
            method_exists($operation, 'getTargetPackage') => $operation->getTargetPackage(),
            default                                       => null,
        };

        if (! $package instanceof PackageInterface || $package->getType() !== self::PACKAGE_TYPE) {
            return null;
        }

        return $package;
    }
}
