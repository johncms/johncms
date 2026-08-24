<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Infrastructure\Storage;

use Johncms\Storage\StorageException;
use Johncms\Storage\StorageInterface;

/**
 * Where the pictures of the albums live.
 *
 * The names of the two files of a photo are columns of the row (`img_name`, `tmb_name`); the
 * directory they sit in is the one thing the module decides, and it used to be spelled out in
 * six use cases at once.
 */
final readonly class AlbumPhotoStorage
{
    public function __construct(
        private StorageInterface $storage,
    ) {
    }

    /**
     * Path of one file of a photo on the disk.
     *
     * Public because a picture is copied to the profile from here, and a copy needs the address
     * of both sides.
     */
    public function path(int $userId, string $name): string
    {
        // The name comes out of the database, and it ends up in a path: an old row with a
        // directory separator in it must not be able to reach outside the album.
        return 'users/album/' . $userId . '/' . basename($name);
    }

    public function exists(int $userId, string $name): bool
    {
        return $name !== '' && $this->storage->exists($this->path($userId, $name));
    }

    /**
     * Address of the picture, or an empty string when the file is missing — a row whose file
     * somebody deleted by hand is a state the album has always tolerated.
     */
    public function url(int $userId, string $name): string
    {
        return $this->exists($userId, $name) ? $this->storage->url($this->path($userId, $name)) : '';
    }

    /**
     * @param callable(string): void $generate Writes the picture to the path it is given.
     * @throws StorageException
     * @throws \Throwable Whatever the handler throws.
     */
    public function store(int $userId, string $name, callable $generate): void
    {
        $this->storage->storeGenerated($this->path($userId, $name), $generate);
    }

    public function delete(int $userId, string $name): void
    {
        if ($name === '') {
            return;
        }

        $this->storage->delete($this->path($userId, $name));
    }
}
