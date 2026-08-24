<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Storage;

use Johncms\Storage\StorageException;
use Johncms\Storage\StorageInterface;

/**
 * The files attached to forum messages the old way — one directory, the name in a column.
 *
 * Not to be confused with the attachments of the editor, which are registered in the `files`
 * table and go through Johncms\Files\FileStore. These predate it: seven places used to build
 * `UPLOAD_PATH . 'forum/attach/'` themselves, including two in the admin module.
 */
final readonly class ForumAttachmentStorage
{
    private const string DIRECTORY = 'forum/attach';

    /** What the preview endpoint can make a picture of. */
    private const array IMAGE_EXTENSIONS = ['gif', 'jpg', 'jpeg', 'png'];

    public function __construct(
        private StorageInterface $storage,
    ) {
    }

    public function path(string $filename): string
    {
        // The name is a column of a row that predates every validation this CMS has now: it must
        // not be able to point anywhere but into the attachment directory.
        return self::DIRECTORY . '/' . basename($filename);
    }

    public function exists(string $filename): bool
    {
        return $filename !== '' && $this->storage->exists($this->path($filename));
    }

    public function url(string $filename): string
    {
        return $this->storage->url($this->path($filename));
    }

    /**
     * Size in bytes, or zero when the file is missing — a row whose file is gone is a state the
     * forum has always tolerated, and a listing of files must not fail because of one.
     */
    public function size(string $filename): int
    {
        return $this->exists($filename) ? $this->storage->size($this->path($filename)) : 0;
    }

    public function isImage(string $filename): bool
    {
        return in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, true);
    }

    /**
     * @throws StorageException
     */
    public function storeFile(string $filename, string $localPath): void
    {
        $this->storage->storeFile($this->path($filename), $localPath);
    }

    public function delete(string $filename): void
    {
        if ($filename === '') {
            return;
        }

        $this->storage->delete($this->path($filename));
    }

    /**
     * Run the handler against the attachment as a file on this server.
     *
     * What the preview endpoint needs: the thumbnail generator works with paths.
     *
     * @template T
     * @param callable(string): T $handler
     * @return T
     * @throws StorageException
     * @throws \Throwable Whatever the handler throws.
     */
    public function withLocalCopy(string $filename, callable $handler): mixed
    {
        return $this->storage->withLocalCopy($this->path($filename), $handler);
    }
}
