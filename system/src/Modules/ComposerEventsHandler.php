<?php

declare(strict_types=1);

namespace Johncms\Modules;

use Composer\Installer\PackageEvent;
use Johncms\Application;
use Johncms\Container\ContainerFactory;

/** @noinspection PhpUnused */

class ComposerEventsHandler
{
    /** @noinspection PhpUnused */
    public static function postModuleInstall(PackageEvent $event): void
    {
        // Check if system installed
        if (is_file(dirname(__DIR__, 3) . '/config/autoload/database.local.php')) {
            require_once $event->getComposer()->getConfig()->get('vendor-dir') . '/autoload.php';
            $installedPackage = $event->getOperation()->getPackage()->getPrettyName();

            ComposerEventsHandler::runApp();
            if (ComposerEventsHandler::isJohnCMSModule($installedPackage)) {
                $moduleManager = new ModuleManager($installedPackage);
                $moduleManager->install();
                $moduleManager->afterInstall();
            }
        }
    }

    /** @noinspection PhpUnused */
    public static function postModuleUpdate(PackageEvent $event): void
    {
        // Check if system installed
        if (is_file(dirname(__DIR__, 3) . '/config/autoload/database.local.php')) {
            require_once $event->getComposer()->getConfig()->get('vendor-dir') . '/autoload.php';
            $installedPackage = $event->getOperation()->getTargetPackage()->getPrettyName();

            ComposerEventsHandler::runApp();
            if (ComposerEventsHandler::isJohnCMSModule($installedPackage)) {
                $moduleManager = new ModuleManager($installedPackage);
                $moduleManager->update();
                $moduleManager->afterUpdate();
            }
        }
    }

    /** @noinspection PhpUnused */
    public static function preModuleUninstall(PackageEvent $event): void
    {
        // Check if system installed
        if (is_file(dirname(__DIR__, 3) . '/config/autoload/database.local.php')) {
            require_once $event->getComposer()->getConfig()->get('vendor-dir') . '/autoload.php';
            $installedPackage = $event->getOperation()->getPackage()->getPrettyName();

            ComposerEventsHandler::runApp();
            if (ComposerEventsHandler::isJohnCMSModule($installedPackage)) {
                (new ModuleManager($installedPackage))->uninstall();
            }
        }
    }

    public static function runApp(): void
    {
        $container = ContainerFactory::getContainer();
        $application = $container->get(Application::class);
        $application->run();
    }

    public static function isJohnCMSModule(string $moduleName): bool
    {
        return is_dir(MODULES_PATH . $moduleName);
    }
}
