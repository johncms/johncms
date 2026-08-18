<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Infrastructure\Storage;

use Johncms\Storage\StorageException;
use Johncms\Storage\StorageInterface;

/**
 * The cover of an article, kept in three sizes under one directory per size.
 *
 * The sizes are an enum rather than three strings, because the four places that used to build
 * these paths each spelled the directory names out again — and one of them checked `small` to
 * decide whether to delete `big` and `orig`.
 */
final readonly class LibraryCoverStorage
{
    public function __construct(
        private StorageInterface $storage,
    ) {
    }

    public function exists(int $articleId, LibraryCoverSize $size): bool
    {
        return $this->storage->exists($this->path($articleId, $size));
    }

    public function url(int $articleId, LibraryCoverSize $size): string
    {
        return $this->storage->url($this->path($articleId, $size));
    }

    /**
     * @throws StorageException
     */
    public function read(int $articleId, LibraryCoverSize $size): string
    {
        return $this->storage->read($this->path($articleId, $size));
    }

    /**
     * @param callable(string): void $generate Writes the picture to the path it is given.
     * @throws StorageException
     * @throws \Throwable Whatever the handler throws.
     */
    public function store(int $articleId, LibraryCoverSize $size, callable $generate): void
    {
        $this->storage->storeGenerated($this->path($articleId, $size), $generate);
    }

    /**
     * Remove every size of the cover.
     */
    public function delete(int $articleId): void
    {
        foreach (LibraryCoverSize::cases() as $size) {
            $this->storage->delete($this->path($articleId, $size));
        }
    }

    public function path(int $articleId, LibraryCoverSize $size): string
    {
        return 'library/images/' . $size->value . '/' . $articleId . '.png';
    }
}
