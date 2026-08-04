<?php

declare(strict_types=1);

namespace Johncms\View\Asset;

use Johncms\View\Theme\ThemeChainResolver;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Turns a theme-relative asset path into the URL it is served at.
 *
 * The file is looked up along the same theme chain as templates are, so a theme overrides an
 * image or a stylesheet exactly the way it overrides a template — by carrying its own copy.
 */
final class AssetResolver
{
    /** @var array<string, string|null> */
    private array $resolved = [];

    public function __construct(
        private readonly ThemeChainResolver $themeChain,
        private readonly LoggerInterface $logger,
        private readonly string $publicThemesPath = PUBLIC_THEMES_PATH,
        private readonly bool $strict = DEBUG,
    ) {
    }

    /**
     * @param bool $versioned Append the modification time, so a changed file is not served from
     *                        the browser cache.
     */
    public function url(string $path, bool $versioned = false): string
    {
        $file = $this->locate($path);

        if ($file === null) {
            // A missing asset is a broken image, not a broken site: in production the page is
            // still served and the problem goes to the log. In development it is raised.
            if ($this->strict) {
                throw new RuntimeException('Unable to locate the asset: ' . $path);
            }

            $this->logger->warning('Unable to locate the asset', ['asset' => $path]);

            return '';
        }

        $url = $this->urlOf($file);

        return $versioned ? $url . '?v=' . filemtime($file) : $url;
    }

    public function exists(string $path): bool
    {
        return $this->locate($path) !== null;
    }

    private function locate(string $path): ?string
    {
        $path = ltrim($path, '/');

        if (array_key_exists($path, $this->resolved)) {
            return $this->resolved[$path];
        }

        foreach ($this->themeChain->resolve((string) config('johncms.skindef', 'default')) as $theme) {
            $file = realpath($this->publicThemesPath . $theme->name . DS . 'assets' . DS . $path);

            if ($file !== false && is_file($file)) {
                return $this->resolved[$path] = $file;
            }
        }

        return $this->resolved[$path] = null;
    }

    private function urlOf(string $file): string
    {
        $base = rtrim((string) realpath(PUBLIC_PATH), DS);

        return str_replace(DS, '/', substr($file, strlen($base)));
    }
}
