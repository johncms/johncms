<?php

declare(strict_types=1);

namespace Johncms\View\Twig;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Finds template files: every one that ships with the installation, and the logical names the
 * active theme resolves them under.
 */
final readonly class TemplateFinder
{
    public const EXTENSION = 'twig';

    public function __construct(
        private TemplatePathRegistry $registry,
        private string $themesPath = THEMES_PATH,
        private string $modulesPath = MODULES_PATH,
    ) {
    }

    /**
     * Every template file in the installation, whichever theme or module it belongs to. Linting
     * covers the templates of inactive themes too — they are shipped code as well.
     *
     * @return array<string>
     */
    public function all(): array
    {
        $files = [];
        foreach ([$this->themesPath, $this->modulesPath] as $root) {
            foreach ($this->filesUnder($root) as $file) {
                $files[$file] = $file;
            }
        }

        sort($files);

        return $files;
    }

    /**
     * The names the templates of the active theme are reachable under, one per name: a template
     * overridden by the theme is compiled from the file that actually wins the lookup.
     *
     * @return array<string>
     */
    public function names(): array
    {
        $names = [];
        foreach ($this->registry->paths((string) config('johncms.skindef', 'default')) as $namespace => $directories) {
            foreach ($directories as $directory) {
                foreach ($this->filesUnder($directory) as $file) {
                    $relative = str_replace(DS, '/', substr($file, strlen(rtrim($directory, DS)) + 1));
                    $name = '@' . $namespace . '/' . $relative;
                    $names[$name] = $name;
                }
            }
        }

        return array_values($names);
    }

    /**
     * @return array<string>
     */
    private function filesUnder(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === self::EXTENSION) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
