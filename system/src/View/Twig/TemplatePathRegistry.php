<?php

declare(strict_types=1);

namespace Johncms\View\Twig;

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
 *     @<name>   themes/<chain>/templates/<name>  +  modules/<vendor>/<name>/templates
 *
 * A module is listed by its key (`johncms/news`), but its namespace is the name alone (`news`):
 * that is what templates say, and a theme overriding them carries a directory of that name.
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
     * @param array<string>|null                      $modules Keys of the modules to register
     *                                                             (`johncms/news`). Defaults to
     *                                                             the ones the registry loads.
     */
    public function __construct(
        private ThemeChainResolver $themeChain,
        private iterable $providers = [],
        private string $themesPath = THEMES_PATH,
        private string $modulesPath = MODULES_PATH,
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

        foreach ($this->installedModules() as $module) {
            $namespace = basename($module);
            $paths[$namespace] = array_merge(
                $paths[$namespace] ?? [],
                $this->suffixed($themeDirectories, $namespace),
                [$this->modulesPath . $module . DS . 'templates']
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
     * @return array<string>
     */
    private function installedModules(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        // A switched-off module keeps its templates on disk, and they must stop resolving with it:
        // a namespace that still answers is a page of a module the site is not running.
        return array_keys(($this->registry ?? ModuleRegistryFactory::registry())->enabled());
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
