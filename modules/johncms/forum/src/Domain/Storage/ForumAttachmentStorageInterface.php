<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Storage;

use Johncms\Storage\StorageException;
use Throwable;

/**
 * The files attached to forum posts: one directory, the name of each in a row of `cms_forum_files`.
 * Every method takes that name, never a path.
 */
interface ForumAttachmentStorageInterface
{
    /** The path of the file on the disk, inside the directory of the attachments whatever the name holds. */
    public function path(string $filename): string;

    public function exists(string $filename): bool;

    public function url(string $filename): string;

    /** Size in bytes, or zero when the file is missing. */
    public function size(string $filename): int;

    public function isImage(string $filename): bool;

    /**
     * @throws StorageException
     */
    public function storeFile(string $filename, string $localPath): void;

    public function delete(string $filename): void;

    /**
     * Run the handler against the attachment as a file on this server.
     *
     * @template T
     * @param callable(string): T $handler
     * @return T
     * @throws StorageException
     * @throws Throwable Whatever the handler throws.
     */
    public function withLocalCopy(string $filename, callable $handler): mixed;
}
