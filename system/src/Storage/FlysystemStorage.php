<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Storage;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;

/**
 * The disk of the CMS, built on flysystem.
 *
 * The only class in the codebase that names the library. Everything else takes
 * StorageInterface, which is what keeps an upgrade of flysystem — or a move to something else
 * entirely — inside this file.
 */
final readonly class FlysystemStorage implements StorageInterface
{
    /**
     * @param FilesystemOperator $filesystem The disk itself.
     * @param string $visibility  Written into every store() call. The local adapter applies its
     *                            permission map only when it is told the visibility; left out,
     *                            the mode of a stored file is whatever umask allows, which on a
     *                            server with a strict umask means a file the web server cannot
     *                            read.
     * @param string $baseUrl     Base URL of a public disk, empty for a private one.
     * @param string|null $localRoot Root directory when the files really are on this server, so
     *                            withLocalCopy() can hand out the file instead of copying it.
     * @param string $temporaryDirectory Where copies of remote files are made.
     */
    public function __construct(
        private FilesystemOperator $filesystem,
        private string $visibility = 'public',
        private string $baseUrl = '',
        private ?string $localRoot = null,
        private string $temporaryDirectory = CACHE_PATH . 'storage',
    ) {
    }

    public function store(string $path, string $contents): void
    {
        $this->write($path, fn() => $this->filesystem->write($path, $contents, ['visibility' => $this->visibility]));
    }

    public function storeStream(string $path, mixed $contents): void
    {
        $this->write($path, fn() => $this->filesystem->writeStream($path, $contents, ['visibility' => $this->visibility]));
    }

    public function storeFile(string $path, string $localFile): void
    {
        $stream = @fopen($localFile, 'rb');
        if ($stream === false) {
            throw new StorageException(sprintf('Could not read the local file "%s".', $localFile));
        }

        try {
            $this->storeStream($path, $stream);
        } finally {
            // Flysystem closes the stream it was given, but only once it got that far.
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function storeGenerated(string $path, callable $generate): void
    {
        $temporary = $this->temporaryPath($path);

        try {
            $generate($temporary);

            if (! is_file($temporary)) {
                throw new StorageException(
                    sprintf('Nothing was generated for "%s": the handler wrote no file.', $path)
                );
            }

            $this->storeFile($path, $temporary);
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    public function read(string $path): string
    {
        return $this->guard(fn() => $this->filesystem->read($path));
    }

    public function readStream(string $path): mixed
    {
        return $this->guard(fn() => $this->filesystem->readStream($path));
    }

    public function exists(string $path): bool
    {
        return $this->guard(fn() => $this->filesystem->fileExists($path));
    }

    public function delete(string $path): void
    {
        $this->guard(fn() => $this->filesystem->delete($path));
    }

    public function deleteDirectory(string $path): void
    {
        $this->guard(fn() => $this->filesystem->deleteDirectory($path));
    }

    public function copy(string $from, string $to): void
    {
        $this->write($to, fn() => $this->filesystem->copy($from, $to, ['visibility' => $this->visibility]));
    }

    public function size(string $path): int
    {
        return $this->guard(fn() => $this->filesystem->fileSize($path));
    }

    public function mimeType(string $path): string
    {
        return $this->guard(fn() => $this->filesystem->mimeType($path));
    }

    public function lastModified(string $path): int
    {
        return $this->guard(fn() => $this->filesystem->lastModified($path));
    }

    public function url(string $path): string
    {
        if ($this->baseUrl === '') {
            return '';
        }

        // Segment by segment: a file stored under a name with a space or a non-latin letter has
        // to survive into a URL a browser will request unchanged.
        $segments = array_map(rawurlencode(...), explode('/', trim($path, '/')));

        return rtrim($this->baseUrl, '/') . '/' . implode('/', $segments);
    }

    public function withLocalCopy(string $path, callable $handler): mixed
    {
        if ($this->localRoot !== null) {
            if (! $this->exists($path)) {
                throw new StorageException(sprintf('The file "%s" is not on the disk.', $path));
            }

            return $handler($this->localRoot . DIRECTORY_SEPARATOR . ltrim($path, '/'));
        }

        $temporary = $this->temporaryPath($path);

        try {
            $this->copyToLocalFile($path, $temporary);

            return $handler($temporary);
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    /**
     * Write, and give the directories the write had to create the visibility of the disk.
     *
     * The local adapter creates them with plain mkdir(), which subtracts umask from the mode it
     * is given: on a server with a strict umask the file would land in a directory the web
     * server cannot enter, and the permission map of the configuration would be quietly
     * overruled. The directories are collected before the write, because afterwards there is no
     * telling which of them are new.
     *
     * @param callable(): void $write
     * @throws StorageException
     */
    private function write(string $path, callable $write): void
    {
        $created = $this->missingDirectories($path);

        $this->guard($write);

        foreach ($created as $directory) {
            $this->guard(fn() => $this->filesystem->setVisibility($directory, $this->visibility));
        }
    }

    /**
     * Directories of the path that are not there yet, outermost first.
     *
     * Only ever asked of a local disk: an object store has no directories to set a mode on, and
     * every check would be a request over the network.
     *
     * @return list<string>
     */
    private function missingDirectories(string $path): array
    {
        if ($this->localRoot === null) {
            return [];
        }

        $segments = explode('/', trim($path, '/'));
        array_pop($segments);

        $directories = [];
        $current = '';
        foreach ($segments as $segment) {
            $current = $current === '' ? $segment : $current . '/' . $segment;

            if (! $this->guard(fn() => $this->filesystem->directoryExists($current))) {
                $directories[] = $current;
            }
        }

        return $directories;
    }

    private function copyToLocalFile(string $path, string $target): void
    {
        $source = $this->readStream($path);
        $destination = @fopen($target, 'wb');

        if ($destination === false) {
            if (is_resource($source)) {
                fclose($source);
            }

            throw new StorageException(sprintf('Could not open "%s" for a local copy of "%s".', $target, $path));
        }

        try {
            if (stream_copy_to_stream($source, $destination) === false) {
                throw new StorageException(sprintf('Could not copy "%s" to the local file "%s".', $path, $target));
            }
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
            fclose($destination);
        }
    }

    /**
     * The extension of the source is kept: the image processor reads the format of what it
     * writes from it, and getID3 uses it as a hint.
     */
    private function temporaryPath(string $path): string
    {
        if (! is_dir($this->temporaryDirectory) && ! @mkdir($this->temporaryDirectory, 0777, true) && ! is_dir($this->temporaryDirectory)) {
            throw new StorageException(
                sprintf('Could not create the temporary directory "%s".', $this->temporaryDirectory)
            );
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . uniqid('storage_', true)
            . ($extension === '' ? '' : '.' . $extension);
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     * @throws StorageException
     */
    private function guard(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (FilesystemException $exception) {
            throw new StorageException($exception->getMessage(), 0, $exception);
        }
    }
}
