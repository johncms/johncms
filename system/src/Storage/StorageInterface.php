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
     * Write a file the handler produces, wherever it has to be produced.
     *
     * The counterpart of withLocalCopy() for the other direction: everything that generates a
     * file — the image processor above all — writes to a path, so the handler is given one,
     * and what it wrote ends up on the disk. The temporary file is removed afterwards, whether
     * the handler returned or threw.
     *
     * The path it is given keeps the extension of the target, because that is what the image
     * processor takes the output format from.
     *
     * @param callable(string): void $generate
     * @throws StorageException
     * @throws \Throwable Whatever the handler throws travels through unchanged: it is the
     *                    caller's failure to make sense of, not the disk's.
     */
    public function storeGenerated(string $path, callable $generate): void;

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
     * Remove a directory and everything under it. A directory that is not there is not an error.
     *
     * @throws StorageException
     */
    public function deleteDirectory(string $path): void;

    /**
     * Copy a file to another path of the same disk, replacing whatever is there.
     *
     * @throws StorageException
     */
    public function copy(string $from, string $to): void;

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
     * When the file was last written, as a Unix timestamp.
     *
     * What a cache-busting parameter is built from, and what tells a generated preview that its
     * source has been replaced.
     *
     * @throws StorageException
     */
    public function lastModified(string $path): int;

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
     * @throws \Throwable Whatever the handler throws travels through unchanged.
     */
    public function withLocalCopy(string $path, callable $handler): mixed;
}
