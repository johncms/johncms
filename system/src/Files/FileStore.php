<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Files;

use Johncms\FileInfo;
use Johncms\Http\UploadedFileDTO;
use Johncms\Storage\StorageException;
use Johncms\Storage\StorageInterface;
use Johncms\Storage\StorageRegistryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Files the CMS keeps track of: written to a disk *and* registered in the `files` table.
 *
 * The disk knows nothing about the database and the repository knows nothing about disks; this
 * is the one place they are put together, and the only one modules go through. Writing to a
 * disk and inserting a row separately is what used to leave the two disagreeing.
 *
 * What happens when only half of it succeeds is decided here, and the two halves are ordered
 * differently on purpose:
 *
 * - **Storing** writes the file first. If the row cannot be inserted afterwards, the file just
 *   written is removed again — a row is what everything else navigates by, so a file without
 *   one is invisible anyway.
 * - **Deleting** removes the row first. If the disk then fails, the failure is logged rather
 *   than thrown: the row is already gone, so as far as the site is concerned the file is
 *   deleted. The other order would leave a row pointing at nothing — broken links on pages,
 *   a download that 404s — which is worse than a few bytes nobody reclaims.
 */
final readonly class FileStore
{
    /**
     * Route that streams a stored file, used for the disks that have no public address of their
     * own. Kept next to the store rather than in the controller: this is what fills it into
     * every URL the modules render.
     */
    public const string DOWNLOAD_PATH = '/file/';

    public function __construct(
        private StorageRegistryInterface $storages,
        private FileRepositoryInterface $files,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Store an uploaded file and register it.
     *
     * @param string $directory Directory of the disk to store under, e.g. `forum_files`.
     * @param string|null $disk Name of the disk; null stores on the default one.
     * @throws FileStoreException
     */
    public function storeUpload(UploadedFileDTO $upload, string $directory, ?string $disk = null): StoredFileDTO
    {
        if (! $upload->isValid()) {
            throw new FileStoreException(
                sprintf('The upload "%s" failed with error %d.', $upload->clientName ?? '', $upload->error)
            );
        }

        return $this->storeLocalFile($upload->tmpPath, $directory, $upload->clientName, $disk);
    }

    /**
     * The same for a set of uploads, skipping the ones the browser failed to send.
     *
     * @param UploadedFileDTO[] $uploads
     * @return list<StoredFileDTO>
     * @throws FileStoreException
     */
    public function storeUploads(array $uploads, string $directory, ?string $disk = null): array
    {
        $stored = [];
        foreach ($uploads as $upload) {
            if (! $upload->isValid()) {
                continue;
            }

            $stored[] = $this->storeUpload($upload, $directory, $disk);
        }

        return $stored;
    }

    /**
     * Store a file that is already on the local filesystem: an import, or a picture the image
     * processor has just written.
     *
     * @param string|null $name Name to register it under; the name of the local file by default.
     * @throws FileStoreException
     */
    public function storeLocalFile(
        string $localPath,
        string $directory,
        ?string $name = null,
        ?string $disk = null,
    ): StoredFileDTO {
        if (! is_file($localPath)) {
            throw new FileStoreException(sprintf('The file "%s" does not exist.', $localPath));
        }

        $info = new FileInfo($localPath);
        $name = $name === null || $name === '' ? (string) $info->getCleanName() : $name;

        return $this->store(
            disk: $disk,
            directory: $directory,
            name: $name,
            hash: $info->getMd5(),
            sha1: $info->getSha1(),
            size: (int) $info->getSize(),
            extension: $this->extension($name, $info->getExtension()),
            write: static fn(StorageInterface $storage, string $path) => $storage->storeFile($path, $localPath),
        );
    }

    /**
     * Store contents the CMS generated itself.
     *
     * @throws FileStoreException
     */
    public function storeContents(
        string $contents,
        string $name,
        string $directory,
        ?string $disk = null,
    ): StoredFileDTO {
        return $this->store(
            disk: $disk,
            directory: $directory,
            name: $name,
            hash: md5($contents),
            sha1: sha1($contents),
            size: strlen($contents),
            extension: $this->extension($name, ''),
            write: static fn(StorageInterface $storage, string $path) => $storage->store($path, $contents),
        );
    }

    public function find(int $id): ?StoredFileDTO
    {
        $file = $this->files->findById($id);

        return $file === null ? null : $this->toDTO($file);
    }

    /**
     * Open a stored file for handing it to a visitor.
     *
     * How a file on a disk that is not public reaches the browser: a controller asks for this,
     * decides whether the visitor may have it, and streams it. Null when nothing is registered
     * under the id.
     *
     * @throws FileStoreException when the row is there but the disk will not give the file up.
     */
    public function openStream(int $id): ?StoredFileStream
    {
        $file = $this->files->findById($id);
        if ($file === null) {
            return null;
        }

        try {
            $disk = $this->storages->disk($file->storage);

            return new StoredFileStream(
                stream: $disk->readStream($file->path),
                name: $file->name,
                mimeType: $disk->mimeType($file->path),
                size: $file->size,
            );
        } catch (StorageException $exception) {
            throw new FileStoreException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param array<mixed> $ids
     * @return list<StoredFileDTO>
     */
    public function getByIds(array $ids): array
    {
        return $this->files->getByIds($this->identifiers($ids))
            ->map(fn(StoredFile $file): StoredFileDTO => $this->toDTO($file))
            ->values()
            ->all();
    }

    /**
     * Remove the file and its row.
     *
     * An id nothing is registered under is not an error: deleting twice has to be as harmless
     * as deleting once, so a caller retrying after a failure needs no guard around this.
     */
    public function delete(int $id): void
    {
        $this->deleteMany([$id]);
    }

    /**
     * The same for a set of files: the rows go in one statement, and a disk failing on one file
     * does not stop the rest.
     *
     * @param array<mixed> $ids Anything that is not an identifier is ignored: the attachments of
     *                          a post are kept in a JSON column, so what comes back from it may
     *                          hold strings, nulls, or a value somebody edited by hand.
     */
    public function deleteMany(array $ids): void
    {
        $files = $this->files->getByIds($this->identifiers($ids));
        if ($files->isEmpty()) {
            return;
        }

        $this->files->deleteByIds($files->pluck('id')->all());

        foreach ($files as $file) {
            try {
                $this->storages->disk($file->storage)->delete($file->path);
            } catch (StorageException $exception) {
                // The row is gone, so the file is deleted as far as the site is concerned. What
                // is left is a file nobody points at, which the log is for.
                $this->logger->error(
                    'Could not remove a stored file from its disk.',
                    [
                        'exception' => $exception,
                        'file_id'   => $file->id,
                        'disk'      => $file->storage,
                        'path'      => $file->path,
                    ]
                );
            }
        }
    }

    /**
     * Of the given files, the ones stored under the directory.
     *
     * What a module attaching files to its own records checks the identifiers it was handed
     * against: an id that belongs to somebody else's upload directory is not its file, however
     * the browser came by it.
     *
     * @param array<mixed> $ids
     * @return list<int>
     */
    public function filterIdsInDirectory(array $ids, string $directory): array
    {
        return $this->files->getIdsInDirectory($this->identifiers($ids), $directory);
    }

    /**
     * @param array<mixed> $ids
     * @return list<int>
     */
    private function identifiers(array $ids): array
    {
        $identifiers = [];
        foreach ($ids as $id) {
            $identifier = filter_var($id, FILTER_VALIDATE_INT);
            if ($identifier !== false) {
                $identifiers[] = $identifier;
            }
        }

        return $identifiers;
    }

    /**
     * @param callable(StorageInterface, string): void $write
     * @throws FileStoreException
     */
    private function store(
        ?string $disk,
        string $directory,
        string $name,
        string $hash,
        string $sha1,
        int $size,
        string $extension,
        callable $write,
    ): StoredFileDTO {
        $diskName = $disk ?? $this->storages->defaultName();
        $storage = $this->storages->disk($diskName);
        $path = $this->path($directory, $hash, $extension);

        try {
            // The path is built from the hash of the contents, so a file that is already there
            // is byte for byte the file being stored. Writing it again would only cost the time.
            $written = ! $storage->exists($path);
            if ($written) {
                $write($storage, $path);
            }
        } catch (StorageException $exception) {
            throw new FileStoreException($exception->getMessage(), 0, $exception);
        }

        try {
            $file = $this->files->create(
                [
                    'storage' => $diskName,
                    'name'    => $name,
                    'path'    => $path,
                    'size'    => $size,
                    'md5'     => $hash,
                    'sha1'    => $sha1,
                ]
            );
        } catch (Throwable $exception) {
            $this->rollBack($storage, $path, $written);

            throw new FileStoreException(
                sprintf('Could not register the file "%s": %s', $name, $exception->getMessage()),
                0,
                $exception
            );
        }

        return $this->toDTO($file, $storage);
    }

    /**
     * Undo a write whose row could not be inserted.
     *
     * Only what this call wrote: an identical file that was already on the disk belongs to the
     * rows that were there before.
     */
    private function rollBack(StorageInterface $storage, string $path, bool $written): void
    {
        if (! $written) {
            return;
        }

        try {
            $storage->delete($path);
        } catch (StorageException $exception) {
            $this->logger->error(
                'Could not remove a file whose registration failed.',
                ['exception' => $exception, 'path' => $path]
            );
        }
    }

    /**
     * Two levels of subdirectories under the module directory, named after the hash: a busy site
     * stores files by the thousand, and a single flat directory of them is slow on most
     * filesystems.
     */
    private function path(string $directory, string $hash, string $extension): string
    {
        $directory = trim($directory, '/');
        $path = ($directory === '' ? '' : $directory . '/')
            . substr($hash, 0, 2) . '/'
            . substr($hash, 2, 2) . '/'
            . substr($hash, 4, 2) . '/'
            . $hash;

        return $extension === '' ? $path : $path . '.' . $extension;
    }

    /**
     * The extension the file is stored under, taken from the name it was uploaded with and
     * reduced to letters and digits.
     *
     * The name comes from the visitor, and it ends up in a path: anything else in it — a slash,
     * a second dot, a trailing space — has no business being there.
     */
    private function extension(string $name, string $fallback): string
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        if ($extension === '') {
            $extension = $fallback;
        }

        $extension = mb_strtolower($extension);

        return (string) preg_replace('/[^a-z0-9]/', '', mb_substr($extension, 0, 10));
    }

    private function toDTO(StoredFile $file, ?StorageInterface $storage = null): StoredFileDTO
    {
        $storage ??= $this->storages->disk($file->storage);

        return new StoredFileDTO(
            id: $file->id,
            name: $file->name,
            size: $file->size,
            url: $this->url($file, $storage),
        );
    }

    /**
     * Where the file is reached at: the address of the disk when it is public, and the route
     * that streams it when it is not.
     *
     * Moving a directory to a private disk therefore changes nothing in the modules — the links
     * they render keep working, they only start going through a controller.
     */
    private function url(StoredFile $file, StorageInterface $storage): string
    {
        $url = $storage->url($file->path);

        return $url === '' ? self::DOWNLOAD_PATH . $file->id : $url;
    }
}
