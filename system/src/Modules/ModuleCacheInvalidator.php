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

/**
 * Throws away what the previous set of modules was compiled into.
 *
 * The compiled container holds the services of the modules that were loaded when it was built, and
 * the dumped routes their routes. Installing or switching off a module without clearing them is
 * the classic "installed it and got a 500": the site keeps answering out of a picture of itself
 * that is one module out of date.
 *
 * Called at the end of every operation on a module, whether it succeeded or not — a run that
 * failed halfway is exactly when the two must not disagree.
 */
final readonly class ModuleCacheInvalidator
{
    private const array FILES = ['container.php', 'routes.php'];

    private const array DIRECTORIES = ['twig'];

    public function __construct(private string $cachePath = CACHE_PATH)
    {
    }

    public function invalidate(): void
    {
        foreach (self::FILES as $file) {
            $path = $this->cachePath . $file;

            if (is_file($path)) {
                @unlink($path);
            }
        }

        foreach (self::DIRECTORIES as $directory) {
            $this->removeDirectory($this->cachePath . $directory);
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach ((array) scandir($directory) as $item) {
            if ($item === '.' || $item === '..' || ! is_string($item)) {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }

        @rmdir($directory);
    }
}
