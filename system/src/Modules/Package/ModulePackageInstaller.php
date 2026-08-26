<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Package;

use Johncms\Modules\Manifest\ModuleManifestLoader;
use Johncms\Modules\ModuleRepositoryInterface;
use Throwable;
use ZipArchive;

/**
 * Puts the files of a module on disk, out of an archive.
 *
 * Nothing about installing happens here — that is ModuleInstallService, and it runs afterwards on
 * a module that is now simply lying in the directory like any other. What happens here is the file
 * shuffling, and it is written so that a failure at any point leaves the site as it was:
 *
 *  1. unpack into a staging directory under data/, never into modules/;
 *  2. read the key out of the manifest — that is what says where the module belongs;
 *  3. move the previous version aside, if there is one;
 *  4. rename staging into place, which is atomic on one filesystem;
 *  5. put the previous version back if anything above went wrong.
 *
 * Unpacking straight into modules/ would mean a half-written module being loaded by the next
 * request that comes in while the copying is still going on.
 */
final readonly class ModulePackageInstaller
{
    public function __construct(
        private ModulePackageValidator $validator,
        private ModuleRepositoryInterface $modules,
        private ModuleManifestLoader $loader = new ModuleManifestLoader(),
        private string $modulesPath = MODULES_PATH,
        private string $temporaryPath = DATA_PATH . 'tmp',
        private string $backupPath = DATA_PATH . 'backups',
    ) {
    }

    /**
     * The key the archive claims, without unpacking it anywhere permanent.
     *
     * Asked before the files are replaced, so the caller can tell an installation from an update:
     * afterwards the module on disk is the new one either way.
     *
     * @throws ModulePackageException
     */
    public function extractKeyOnly(string $archive): string
    {
        $root = $this->validator->validate($archive);
        $staging = $this->makeStagingDirectory();

        try {
            $this->unpack($archive, $staging);

            return $this->loader->keyOf($staging . DIRECTORY_SEPARATOR . $root);
        } finally {
            $this->removeDirectory($staging);
        }
    }

    /**
     * @return string The key of the module whose files are now in place.
     * @throws ModulePackageException
     */
    public function extract(string $archive): string
    {
        $root = $this->validator->validate($archive);
        $staging = $this->makeStagingDirectory();

        try {
            $this->unpack($archive, $staging);

            $unpacked = $staging . DIRECTORY_SEPARATOR . $root;
            $key = $this->loader->keyOf($unpacked);
            $target = $this->modulesPath . str_replace('/', DIRECTORY_SEPARATOR, $key);

            $this->refuseIfSystemModule($key);
            $this->refuseIfNotWritable($target);

            $backup = $this->moveAside($target, $key);

            try {
                if (! rename($unpacked, $target)) {
                    throw new ModulePackageException(sprintf('Cannot put the module into "%s".', $target));
                }
            } catch (Throwable $exception) {
                $this->restore($backup, $target);

                throw $exception;
            }

            return $key;
        } finally {
            $this->removeDirectory($staging);
        }
    }

    /**
     * @throws ModulePackageException
     */
    private function unpack(string $archive, string $staging): void
    {
        $zip = new ZipArchive();

        if ($zip->open($archive, ZipArchive::RDONLY) !== true) {
            throw new ModulePackageException(sprintf('"%s" cannot be read as a zip archive.', basename($archive)));
        }

        try {
            if (! $zip->extractTo($staging)) {
                throw new ModulePackageException(sprintf('"%s" could not be unpacked.', basename($archive)));
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * @throws ModulePackageException
     */
    private function refuseIfSystemModule(string $key): void
    {
        if ($this->modules->find($key)?->system === true) {
            throw new ModulePackageException(
                sprintf('"%s" is a system module and is replaced by updating the CMS, not from an archive.', $key)
            );
        }
    }

    /**
     * @throws ModulePackageException
     */
    private function refuseIfNotWritable(string $target): void
    {
        $vendor = dirname($target);
        $existing = is_dir($vendor) ? $vendor : dirname($vendor);

        if (! is_writable($existing)) {
            throw new ModulePackageException(
                sprintf('"%s" is not writable, so a module cannot be installed into it.', $existing)
            );
        }

        if (is_dir($target) && ! is_writable($target)) {
            throw new ModulePackageException(sprintf('"%s" is not writable.', $target));
        }
    }

    /**
     * @return string|null Where the previous version went, or null when there was none.
     */
    private function moveAside(string $target, string $key): ?string
    {
        if (! is_dir($target)) {
            $this->makeDirectory(dirname($target));

            return null;
        }

        $this->makeDirectory($this->backupPath);

        $backup = sprintf(
            '%s%smodule-%s-%s',
            rtrim($this->backupPath, DIRECTORY_SEPARATOR),
            DIRECTORY_SEPARATOR,
            str_replace('/', '-', $key),
            date('Y-m-d-His')
        );

        return rename($target, $backup) ? $backup : null;
    }

    private function restore(?string $backup, string $target): void
    {
        if ($backup !== null && is_dir($backup) && ! is_dir($target)) {
            rename($backup, $target);
        }
    }

    private function makeStagingDirectory(): string
    {
        $staging = rtrim($this->temporaryPath, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . bin2hex(random_bytes(8));

        $this->makeDirectory($staging);

        return $staging;
    }

    private function makeDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0o755, true) && ! is_dir($directory)) {
            throw new ModulePackageException(sprintf('Cannot create "%s".', $directory));
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
