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

/**
 * One place the CMS keeps files in — the local upload directory, or an object store.
 *
 * Which library talks to that place is deliberately invisible here, the same way it is for
 * ImageProcessorInterface and HtmlSanitizerInterface: flysystem lives inside FlysystemStorage
 * and nowhere else, so an upgrade of it is a one-file change instead of a sweep across every
 * module that stores a file.
 *
 * Paths are relative to the root of the disk and always use forward slashes. A disk is obtained
 * from StorageRegistryInterface, or injected directly where the name is known at wiring time.
 *
 * The registry does not know about the database, and this interface does not either: writing a
 * file *and* registering it in the `files` table is what Johncms\Files\FileStore is for.
 */
interface StorageInterface
{
    /**
     * Write the contents to the path, replacing whatever is there.
     *
     * Missing directories are created along the way.
     *
     * @throws StorageException
     */
    public function store(string $path, string $contents): void;

    /**
     * The same, reading the contents from an open stream.
     *
     * What an upload or a download takes, because neither has to fit in memory first.
     *
     * @param resource $contents
     * @throws StorageException
     */
    public function storeStream(string $path, mixed $contents): void;

    /**
     * The same, copying a file that is already on the local filesystem.
     *
     * The counterpart of withLocalCopy(): a picture produced by ImageProcessorInterface lands
     * on disk, and this is what puts it on the disk of the CMS.
     *
     * @throws StorageException
     */
    public function storeFile(string $path, string $localFile): void;

    /**
     * @throws StorageException
     */
    public function read(string $path): string;

    /**
     * The contents as a stream, for handing a file to a visitor without loading it into memory.
     *
     * @return resource
     * @throws StorageException
     */
    public function readStream(string $path): mixed;

    /**
     * @throws StorageException
     */
    public function exists(string $path): bool;

    /**
     * Remove the file. A path that is not there is not an error — deleting twice has the same
     * result as deleting once, which is what makes FileStore::delete() safe to retry.
     *
     * @throws StorageException
     */
    public function delete(string $path): void;

    /**
     * Size in bytes.
     *
     * @throws StorageException
     */
    public function size(string $path): int;

    /**
     * @throws StorageException
     */
    public function mimeType(string $path): string;

    /**
     * The URL the file is served at, or an empty string when the disk is not public.
     *
     * A private disk has nothing to return here: its files are handed out by a controller that
     * decides whether this visitor may have them.
     */
    public function url(string $path): string;

    /**
     * Run the handler against a path on the local filesystem and return what it returns.
     *
     * The bridge to everything that can only work with a real path — ImageProcessorInterface,
     * FileInfo, getID3. A local disk passes the file itself and copies nothing; any other disk
     * downloads it to a temporary file and removes that file afterwards, whether the handler
     * returned or threw.
     *
     * The temporary copy keeps the extension of the source, because that is what the image
     * processor reads the format from.
     *
     * @template T
     * @param callable(string): T $handler
     * @return T
     * @throws StorageException
     */
    public function withLocalCopy(string $path, callable $handler): mixed;
}
