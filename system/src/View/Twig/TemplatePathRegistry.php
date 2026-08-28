<?php

declare(strict_types=1);

namespace Johncms\View\Twig;

use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleRegistryFactory;
use Johncms\View\Theme\ThemeChainResolver;

/**
 * Works out which directories every template namespace is searched in, in which order.
 *
 * The layout is a convention, so installing a module is enough to make its templates reachable:
 * nothing about Twig is configured per module.
 *
 *     @theme    themes/<chain>/templates
 *     @admin    themes/<chain>/templates/admin  +  modules/johncms/admin/templates
 *     @<alias>  themes/<chain>/templates/<alias>  +  <directory of the module>/templates
 *
 * The namespace is the alias of the module — the flat name it also uses for its gettext domain and
 * for the source of its migrations — and the directory is the one its manifest was read from. So a
 * module Composer put in vendor/ is reachable exactly like one lying in modules/, and a module
 * whose alias is shorter than its directory (`vasya/old-guestbook` answering to `@guestbook`) is
 * addressed by the short name everywhere.
 *
 * The theme chain comes first everywhere, which is what lets a theme override a template of a
 * module without touching it.
 */
final readonly class TemplatePathRegistry
{
    public const THEME_NAMESPACE = 'theme';

    public const ADMIN_NAMESPACE = 'admin';

    /**
     * @param iterable<TemplatePathProviderInterface> $providers
     * @param array<ModuleManifest>|null              $modules   The modules to register. Defaults
     *                                                           to the ones the registry loads.
     */
    public function __construct(
        private ThemeChainResolver $themeChain,
        private iterable $providers = [],
        private string $themesPath = THEMES_PATH,
        private ?array $modules = null,
        private ?ModuleRegistry $registry = null,
    ) {
    }

    /**
     * @return array<string, array<string>> Namespace to existing directories, most specific first.
     */
    public function paths(string $theme): array
    {
        $themeDirectories = [];
        foreach ($this->themeChain->resolve($theme) as $themeInChain) {
            $themeDirectories[] = $this->themesPath . $themeInChain->name . DS . 'templates';
        }

        $paths = [
            self::THEME_NAMESPACE => $themeDirectories,
            self::ADMIN_NAMESPACE => $this->suffixed($themeDirectories, self::ADMIN_NAMESPACE),
        ];

        foreach ($this->enabledModules() as $manifest) {
            $namespace = $manifest->alias;
            $paths[$namespace] = array_merge(
                $paths[$namespace] ?? [],
                $this->suffixed($themeDirectories, $namespace),
                [$manifest->path . DS . 'templates']
            );
        }

        foreach ($this->providers as $provider) {
            foreach ($provider->paths() as $namespace => $directories) {
                $paths[$namespace] = array_merge($paths[$namespace] ?? [], $directories);
            }
        }

        return array_filter(array_map($this->existing(...), $paths));
    }

    /**
     * @return array<ModuleManifest>
     */
    private function enabledModules(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        // A switched-off module keeps its templates on disk, and they must stop resolving with it:
        // a namespace that still answers is a page of a module the site is not running.
        return ($this->registry ?? ModuleRegistryFactory::registry())->enabled();
    }

    /**
     * @param array<string> $directories
     * @return array<string>
     */
    private function suffixed(array $directories, string $suffix): array
    {
        return array_map(static fn (string $directory): string => $directory . DS . $suffix, $directories);
    }

    /**
     * Twig raises on a path that does not exist, and most of these do not: a theme carries the
     * directories it overrides and nothing else.
     *
     * @param array<string> $directories
     * @return array<string>
     */
    private function existing(array $directories): array
    {
        return array_values(array_filter(array_unique($directories), is_dir(...)));
    }
}
