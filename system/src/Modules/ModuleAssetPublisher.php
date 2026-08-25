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

use Johncms\Modules\Manifest\ModuleManifest;

/**
 * Copies the web-accessible files of a module into the document root, and takes them out again.
 *
 * modules/ lies outside the document root on purpose, so nothing a module ships is reachable by
 * URL until it is put here — under public/modules/<alias>/, by alias rather than by key, so the
 * address stays short: /modules/blog/js/app.js.
 *
 * Only files of known types are copied. Everything in the document root is served by the web
 * server, and a .php or .phar among the assets of a module would be exactly that: code the module
 * put where anyone can run it. A module that needs to answer a request has routes for it.
 *
 * Copying rather than linking by default: symlinks are a way to lose a whole site on a host that
 * follows them, and half of the shared hosting this CMS runs on cannot make one at all. A
 * developer working on a module asks for links explicitly.
 */
final readonly class ModuleAssetPublisher
{
    /** Anything a browser loads and nothing a server executes. */
    private const array ALLOWED = [
        'avif', 'bmp', 'css', 'eot', 'gif', 'ico', 'jpeg', 'jpg', 'js', 'json', 'map', 'mjs',
        'otf', 'png', 'svg', 'ttf', 'txt', 'wasm', 'webm', 'webp', 'woff', 'woff2',
    ];

    public function __construct(private string $publicPath = PUBLIC_PATH)
    {
    }

    /**
     * @return int How many files were published. Zero means the module ships none.
     */
    public function publish(ModuleManifest $manifest, bool $symlink = false): int
    {
        $source = $manifest->path . DIRECTORY_SEPARATOR . $manifest->assets->source;

        if (! is_dir($source)) {
            return 0;
        }

        $target = $this->targetOf($manifest->alias);
        $this->unpublish($manifest->alias);

        if ($symlink) {
            $this->makeDirectory(dirname($target));

            return symlink($source, $target) ? 1 : 0;
        }

        return $this->copyDirectory($source, $target);
    }

    public function unpublish(string $alias): void
    {
        $target = $this->targetOf($alias);

        if (is_link($target)) {
            unlink($target);

            return;
        }

        $this->removeDirectory($target);
    }

    public function isPublished(string $alias): bool
    {
        $target = $this->targetOf($alias);

        return is_link($target) || is_dir($target);
    }

    private function targetOf(string $alias): string
    {
        return $this->publicPath . 'modules' . DIRECTORY_SEPARATOR . $alias;
    }

    private function copyDirectory(string $source, string $target): int
    {
        $this->makeDirectory($target);

        $published = 0;
        foreach ((array) scandir($source) as $item) {
            if (! is_string($item) || $item === '.' || $item === '..') {
                continue;
            }

            $from = $source . DIRECTORY_SEPARATOR . $item;
            $to = $target . DIRECTORY_SEPARATOR . $item;

            if (is_dir($from)) {
                $published += $this->copyDirectory($from, $to);
                continue;
            }

            if (! $this->isAllowed($item)) {
                continue;
            }

            if (copy($from, $to)) {
                ++$published;
            }
        }

        // A directory that held nothing publishable leaves nothing behind either.
        if ($published === 0) {
            @rmdir($target);
        }

        return $published;
    }

    private function isAllowed(string $file): bool
    {
        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::ALLOWED, true);
    }

    private function makeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0o755, true);
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach ((array) scandir($directory) as $item) {
            if (! is_string($item) || $item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            is_dir($path) && ! is_link($path) ? $this->removeDirectory($path) : @unlink($path);
        }

        @rmdir($directory);
    }
}
