<?php

declare(strict_types=1);

namespace Johncms\View\Theme;

/**
 * Expands a theme into the chain a template is searched along: the theme itself, its parent, and
 * so on down to the default one.
 *
 * The chain is what makes a theme holding a single overridden template possible — everything it
 * does not carry is found further down.
 */
final readonly class ThemeChainResolver
{
    public function __construct(private ThemeRepositoryInterface $themes)
    {
    }

    /**
     * @return array<ThemeDTO> Most specific theme first.
     */
    public function resolve(string $name): array
    {
        $chain = [];
        $current = $this->themes->find($name);

        // A theme named in the configuration but not installed resolves to the default one rather
        // than to an empty chain: the site keeps rendering, with the default look.
        if ($current === null) {
            $current = $this->themes->find(FilesystemThemeRepository::DEFAULT_THEME);
        }

        while ($current !== null && ! isset($chain[$current->name])) {
            // A parent loop in a hand-written manifest stops the walk instead of taking the site
            // down: what the chain holds so far is still a usable set of templates.
            $chain[$current->name] = $current;
            $current = $current->parent === null ? null : $this->themes->find($current->parent);
        }

        $default = $this->themes->find(FilesystemThemeRepository::DEFAULT_THEME);
        if ($default !== null && ! isset($chain[$default->name])) {
            $chain[$default->name] = $default;
        }

        return array_values($chain);
    }
}
