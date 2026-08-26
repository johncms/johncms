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

use ZipArchive;

/**
 * Looks inside an archive before anything is written to disk.
 *
 * Everything here is about what an archive can do to a filesystem: an entry named ../../config
 * writes outside the directory it was unpacked into, a symlink points wherever it likes, and a
 * few kilobytes can expand into everything the disk has. None of it is exotic — it is what an
 * archive from an unknown author looks like when it means harm.
 *
 * The archive is also expected to look like a module package: one directory at the top, and a
 * module.php inside it. Anything else is somebody's backup, and saying so is more useful than
 * unpacking it and reporting a missing manifest afterwards.
 */
final readonly class ModulePackageValidator
{
    /** Enough for a module with images and a vendor directory; far short of a disk. */
    private const int MAX_UNPACKED_BYTES = 128 * 1024 * 1024;

    private const int MAX_ENTRIES = 5000;

    public function __construct(
        private int $maxUnpackedBytes = self::MAX_UNPACKED_BYTES,
        private int $maxEntries = self::MAX_ENTRIES,
    ) {
    }

    /**
     * @return string The name of the single directory at the top of the archive.
     * @throws ModulePackageException
     */
    public function validate(string $file): string
    {
        if (! is_file($file)) {
            throw new ModulePackageException(sprintf('There is no file "%s".', $file));
        }

        $zip = new ZipArchive();
        $opened = $zip->open($file, ZipArchive::RDONLY);

        if ($opened !== true) {
            throw new ModulePackageException(sprintf('"%s" cannot be read as a zip archive.', basename($file)));
        }

        try {
            return $this->inspect($zip, basename($file));
        } finally {
            $zip->close();
        }
    }

    /**
     * @throws ModulePackageException
     */
    private function inspect(ZipArchive $zip, string $name): string
    {
        if ($zip->numFiles > $this->maxEntries) {
            throw new ModulePackageException(
                sprintf('"%s" holds %d files, which is more than a module has any business holding.', $name, $zip->numFiles)
            );
        }

        $unpacked = 0;
        $roots = [];
        $manifests = [];

        for ($index = 0; $index < $zip->numFiles; ++$index) {
            $stat = $zip->statIndex($index);

            if ($stat === false) {
                throw new ModulePackageException(sprintf('"%s" has an entry that cannot be read.', $name));
            }

            $path = (string) $stat['name'];

            $this->refuseUnsafePath($path, $name);
            $this->refuseSymlink($zip, $index, $path, $name);

            $unpacked += (int) $stat['size'];
            if ($unpacked > $this->maxUnpackedBytes) {
                throw new ModulePackageException(
                    sprintf('"%s" unpacks to more than %d MB.', $name, intdiv($this->maxUnpackedBytes, 1024 * 1024))
                );
            }

            $segments = explode('/', trim($path, '/'));
            $roots[$segments[0]] = true;

            if (count($segments) === 2 && $segments[1] === 'module.php') {
                $manifests[] = $segments[0];
            }
        }

        if (count($roots) !== 1) {
            throw new ModulePackageException(
                sprintf('"%s" must hold exactly one directory with the module in it.', $name)
            );
        }

        if ($manifests === []) {
            throw new ModulePackageException(
                sprintf('"%s" has no module.php, so it is not a module package.', $name)
            );
        }

        return (string) array_key_first($roots);
    }

    /**
     * @throws ModulePackageException
     */
    private function refuseUnsafePath(string $path, string $name): void
    {
        if (str_starts_with($path, '/') || preg_match('#^[a-zA-Z]:#', $path) === 1) {
            throw new ModulePackageException(sprintf('"%s" holds an absolute path: %s', $name, $path));
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                throw new ModulePackageException(
                    sprintf('"%s" holds a path leading outside the module: %s', $name, $path)
                );
            }
        }
    }

    /**
     * A symlink is stored as an entry whose unix mode says so. Unpacked, it points wherever its
     * author chose — including at a file of the site, which the next write would then go through.
     *
     * @throws ModulePackageException
     */
    private function refuseSymlink(ZipArchive $zip, int $index, string $path, string $name): void
    {
        $attributes = 0;
        $external = 0;

        if (! $zip->getExternalAttributesIndex($index, $attributes, $external)) {
            return;
        }

        // The unix mode lives in the high 16 bits of the external attributes.
        $mode = $external >> 16;

        if (($mode & 0xA000) === 0xA000) {
            throw new ModulePackageException(sprintf('"%s" holds a symbolic link: %s', $name, $path));
        }
    }
}
