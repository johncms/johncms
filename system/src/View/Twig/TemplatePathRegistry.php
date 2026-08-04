<?php

declare(strict_types=1);

namespace Johncms\View\Twig;

use Johncms\View\Theme\ThemeChainResolver;

/**
 * Works out which directories every template namespace is searched in, in which order.
 *
 * The layout is a convention, so installing a module is enough to make its templates reachable:
 * nothing about Twig is configured per module.
 *
 *     @theme    themes/<chain>/templates
 *     @admin    themes/<chain>/templates/admin  +  modules/admin/templates
 *     @<module> themes/<chain>/templates/<module>  +  modules/<module>/templates
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
     * @param array<string>|null                      $modules Defaults to the installed modules.
     */
    public function __construct(
        private ThemeChainResolver $themeChain,
        private iterable $providers = [],
        private string $themesPath = THEMES_PATH,
        private string $modulesPath = MODULES_PATH,
        private ?array $modules = null,
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
            $paths[$module] = array_merge(
                $paths[$module] ?? [],
                $this->suffixed($themeDirectories, $module),
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
        return $this->modules ?? array_merge(
            (array) config('modules.installed_modules', []),
            (array) config('modules.system_modules', [])
        );
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
