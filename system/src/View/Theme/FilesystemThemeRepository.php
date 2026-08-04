<?php

declare(strict_types=1);

namespace Johncms\View\Theme;

/**
 * Reads the themes from themes/, one directory per theme, described by an optional theme.php.
 *
 * The manifest is optional so that a theme carrying nothing but templates keeps working: it then
 * inherits from the default theme, which is what a theme without one always meant.
 */
final class FilesystemThemeRepository implements ThemeRepositoryInterface
{
    public const DEFAULT_THEME = 'default';

    private const MANIFEST = 'theme.php';

    /** @var array<string, ThemeDTO>|null */
    private ?array $themes = null;

    public function __construct(private readonly string $themesPath = THEMES_PATH)
    {
    }

    public function find(string $name): ?ThemeDTO
    {
        return $this->all()[$name] ?? null;
    }

    public function exists(string $name): bool
    {
        return $this->find($name) !== null;
    }

    public function all(): array
    {
        if ($this->themes !== null) {
            return $this->themes;
        }

        $themes = [];
        foreach ((array) glob($this->themesPath . '*', GLOB_ONLYDIR) as $directory) {
            $name = basename((string) $directory);
            $themes[$name] = $this->read($name, (string) $directory);
        }

        return $this->themes = $themes;
    }

    private function read(string $name, string $directory): ThemeDTO
    {
        $manifestFile = $directory . DS . self::MANIFEST;
        $manifest = is_file($manifestFile) ? (array) require $manifestFile : [];

        return new ThemeDTO(
            name: $name,
            title: is_string($manifest['name'] ?? null) ? $manifest['name'] : $name,
            parent: $this->parentOf($name, $manifest),
            entries: is_array($manifest['entries'] ?? null) ? $manifest['entries'] : [],
            settings: is_array($manifest['settings'] ?? null) ? $manifest['settings'] : [],
        );
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function parentOf(string $name, array $manifest): ?string
    {
        if (array_key_exists('parent', $manifest)) {
            return is_string($manifest['parent']) ? $manifest['parent'] : null;
        }

        // No manifest, or a manifest that says nothing about inheritance: every theme falls back
        // to the default one, which is the only place holding a complete set of templates.
        return $name === self::DEFAULT_THEME ? null : self::DEFAULT_THEME;
    }
}
