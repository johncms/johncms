<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\View\Asset;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

/**
 * Renders the tags of a Vite entry point.
 *
 * The site is server rendered, so there is no single application shell: every layout
 * asks for the entry point of its own theme. Two modes are supported:
 *
 *  - development, when the Vite dev server is running. It writes its URL into the hot
 *    file, the modules are then loaded from the dev server and styles are injected by
 *    the client script, which enables hot module replacement.
 *  - production, when the tags are built from the manifest of the compiled bundle.
 */
final class Vite
{
    /**
     * Directory of the compiled bundle inside the public directory.
     * Must be kept in sync with the buildDirectory option in vite.config.js.
     */
    private const BUILD_DIRECTORY = 'build';

    /** @var array<string, array>|null */
    private ?array $manifest = null;

    private ?string $devServerUrl = null;

    private bool $devServerChecked = false;

    /**
     * @param string $entry Entry point path, relative to the project root.
     * @param bool $rtl Whether to load the mirrored stylesheets.
     */
    public function tags(string $entry, bool $rtl = false): string
    {
        $entry = ltrim($entry, '/');
        $devServerUrl = $this->devServerUrl();

        if ($devServerUrl !== null) {
            // The dev server injects the styles through the client script.
            return $this->scriptTag($devServerUrl . '/@vite/client')
                . $this->scriptTag($devServerUrl . '/' . $entry);
        }

        $manifest = $this->manifest();

        if (! isset($manifest[$entry]['file'])) {
            throw new InvalidArgumentException('Unable to locate the Vite entry point: ' . $entry);
        }

        $tags = '';

        foreach ($this->collectStyles($entry, $manifest) as $style) {
            $tags .= $this->styleTag($this->assetUrl($rtl ? $this->rtlVariant($style) : $style));
        }

        return $tags . $this->scriptTag($this->assetUrl($manifest[$entry]['file']));
    }

    /**
     * Collects the stylesheets of an entry point and of every chunk it imports.
     *
     * @param array<string, array> $manifest
     * @return string[]
     */
    private function collectStyles(string $entry, array $manifest): array
    {
        $styles = [];
        $visited = [];
        $queue = [$entry];

        while ($queue !== []) {
            $key = array_shift($queue);

            if (isset($visited[$key]) || ! isset($manifest[$key])) {
                continue;
            }

            $visited[$key] = true;

            foreach ($manifest[$key]['css'] ?? [] as $style) {
                $styles[$style] = $style;
            }

            foreach ($manifest[$key]['imports'] ?? [] as $import) {
                $queue[] = $import;
            }
        }

        return array_values($styles);
    }

    /**
     * The build emits a mirrored sibling next to every stylesheet.
     */
    private function rtlVariant(string $style): string
    {
        return preg_replace('/\.css$/', '.rtl.css', $style) ?? $style;
    }

    /**
     * @return array<string, array>
     */
    private function manifest(): array
    {
        if ($this->manifest === null) {
            $manifestFile = PUBLIC_PATH . self::BUILD_DIRECTORY . DS . 'manifest.json';

            if (! is_file($manifestFile)) {
                throw new RuntimeException(
                    'The Vite manifest was not found at ' . $manifestFile . '. Run the "npm run build" command.'
                );
            }

            try {
                $this->manifest = json_decode(
                    (string) file_get_contents($manifestFile),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            } catch (JsonException $exception) {
                throw new RuntimeException('The Vite manifest is not valid JSON: ' . $exception->getMessage());
            }
        }

        return $this->manifest;
    }

    /**
     * Returns the dev server URL, or null when the bundle has to be served from the build.
     */
    private function devServerUrl(): ?string
    {
        if (! $this->devServerChecked) {
            $this->devServerChecked = true;
            $hotFile = PUBLIC_PATH . 'hot';

            if (is_file($hotFile)) {
                $url = trim((string) file_get_contents($hotFile));
                $this->devServerUrl = $url !== '' ? rtrim($url, '/') : null;
            }
        }

        return $this->devServerUrl;
    }

    private function assetUrl(string $file): string
    {
        return '/' . self::BUILD_DIRECTORY . '/' . ltrim($file, '/');
    }

    private function scriptTag(string $url): string
    {
        return '<script type="module" src="' . htmlspecialchars($url, ENT_QUOTES) . '"></script>';
    }

    private function styleTag(string $url): string
    {
        return '<link rel="stylesheet" href="' . htmlspecialchars($url, ENT_QUOTES) . '">';
    }
}
