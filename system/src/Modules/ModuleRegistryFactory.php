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

use Johncms\Database\Migrations\SystemMigrationSourceProvider;
use Johncms\System\i18n\Translator;
use Johncms\View\Twig\TemplatePathRegistry;

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
            reserved: self::reserved(),
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
     * Only for tests: which modules the suite runs against.
     *
     * The state file belongs to the site, and a developer who switched a module off on their own
     * installation would otherwise see the suite fail — the functional tests drive real requests
     * through the real registry. Pointed at a file that does not exist, the registry falls back to
     * the modules of the release, which is what a fresh site has and what CI runs.
     */
    public static function useState(ModuleStateStore $state): void
    {
        self::$state = $state;
        self::$instance = null;
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
     * The names an alias may not take, and what already holds each of them.
     *
     * An alias is a key in several registries at once, and not every holder is a module: the CMS
     * itself answers to "system" as a source of migrations and as a domain of translations, and
     * the theme engine answers to "theme" as a namespace of templates. The check the registry
     * already had compares a module against other modules, so it never saw those.
     *
     * Assembled here rather than inside ModuleRegistry, because this is where the registry is put
     * together and the only place that may reach across into the view and the database without
     * the module subsystem depending on them.
     *
     * "admin" is deliberately absent. It is a namespace of the theme as well, but there the two
     * are meant to meet — @admin is the templates of the theme and of the admin module merged —
     * and reserving the name would mark a module of the release as broken. It is held by that
     * module, and the check between modules covers it. The day the admin panel becomes a module a
     * site can remove, this is the comment to come back to.
     *
     * @return array<string, string> Name to what holds it, in a sentence a person can act on.
     */
    private static function reserved(): array
    {
        return array_merge(
            // One name, two holders in the core: the same "system" names the migrations of the CMS
            // and the domain its strings are written in.
            array_fill_keys(
                [SystemMigrationSourceProvider::NAME, Translator::SYSTEM_DOMAIN],
                'the migrations and the translations of the CMS'
            ),
            [TemplatePathRegistry::THEME_NAMESPACE => 'the templates of the theme'],
        );
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
