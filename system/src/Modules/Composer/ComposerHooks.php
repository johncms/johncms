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
 * So the files arrive, and the site is told about them the next time somebody looks — by this
 * message in the terminal, and by the note that puts the module into the "found on disk" list of
 * the admin panel.
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

        self::note($package->getPrettyName(), $event);

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

    /**
     * Leaves the key where the site will find it: the admin panel lists a module that appeared on
     * disk, and this is what tells it one appeared through Composer rather than by hand.
     */
    private static function note(string $key, PackageEvent $event): void
    {
        $root = dirname($event->getComposer()->getConfig()->get('vendor-dir'));
        $file = $root . '/data/tmp/modules-pending.json';

        if (! is_dir(dirname($file)) && ! mkdir(dirname($file), 0o755, true) && ! is_dir(dirname($file))) {
            return;
        }

        $pending = [];
        if (is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            $pending = is_array($decoded) ? array_filter($decoded, is_string(...)) : [];
        }

        $pending[] = $key;

        @file_put_contents($file, json_encode(array_values(array_unique($pending)), JSON_PRETTY_PRINT));
    }
}
