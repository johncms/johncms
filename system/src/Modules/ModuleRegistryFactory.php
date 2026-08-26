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

/**
 * Builds the registry, and hands out the same one to everybody.
 *
 * A static instance for the same reason PSRContainerFactory keeps one: the list of modules is
 * needed while the container is still being compiled — it decides whose services.php is loaded —
 * so it cannot itself come out of the container. The container is given this instance as well, so
 * a service asking for a ModuleRegistry gets the one the boot already used.
 *
 * The bundled list is read here rather than injected, so that a cached container does not freeze
 * the modules of the release it was compiled under.
 */
final class ModuleRegistryFactory
{
    private static ?ModuleRegistry $instance = null;

    private static ?ModuleRepositoryInterface $modules = null;

    private static ?ModuleStateStore $state = null;

    public static function registry(): ModuleRegistry
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        return self::$instance = new ModuleRegistry(
            modules: self::modules(),
            state: self::state(),
            bundled: self::bundled(),
            safeMode: defined('MODULES_SAFE_MODE') && constant('MODULES_SAFE_MODE') === true,
        );
    }

    /**
     * The container is given these rather than building its own, so that everything in the process
     * shares them. Two instances mean two caches: one of them writes the state file or unpacks a
     * module, and the other keeps answering with what it read a moment earlier — which is how an
     * installed module ends up invisible to the very code installing it.
     */
    public static function modules(): ModuleRepositoryInterface
    {
        // modules/ first: a module a person unpacked there wins over a package of the same name
        // that Composer happens to have in vendor/.
        return self::$modules ??= new ChainModuleRepository([
            new FilesystemModuleRepository(),
            new ComposerModuleRepository(),
        ]);
    }

    public static function state(): ModuleStateStore
    {
        return self::$state ??= new ModuleStateStore();
    }

    public function __invoke(): ModuleRegistry
    {
        return self::registry();
    }

    /**
     * Only for tests: the next call builds a registry again.
     */
    public static function reset(): void
    {
        self::$instance = null;
        self::$modules = null;
        self::$state = null;
    }

    /**
     * The modules of this release. Read straight from the file instead of through config(),
     * because the registry is asked for before the configuration is assembled.
     *
     * @return list<string>
     */
    private static function bundled(): array
    {
        $file = CONFIG_PATH . 'autoload' . DS . 'modules.global.php';

        if (! is_file($file)) {
            return [];
        }

        /** @psalm-suppress UnresolvableInclude */
        $config = require $file;
        $bundled = is_array($config) ? ($config['modules']['bundled'] ?? null) : null;

        if (! is_array($bundled)) {
            return [];
        }

        return array_values(array_filter($bundled, is_string(...)));
    }
}
