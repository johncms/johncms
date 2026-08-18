<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

use Johncms\Storage\StorageException;
use Johncms\Storage\StorageInterface;

/**
 * The two pictures a user has: the avatar next to every message, and the photo of the profile.
 *
 * Where they live used to be spelled out in six places — the profile module wrote them, the
 * Twig runtime built the URL of one, a mutator of the user model built the URLs of the other —
 * each with its own `UPLOAD_PATH . 'users/…'`. One of them getting the name wrong meant an
 * avatar that uploads and never appears, and moving the pictures anywhere meant finding all six.
 */
final readonly class UserImages
{
    public function __construct(
        private StorageInterface $storage,
    ) {
    }

    public function hasAvatar(int $userId): bool
    {
        return $userId > 0 && $this->storage->exists($this->avatarPath($userId));
    }

    /**
     * Address of the avatar, or an empty string when the user has none.
     *
     * Carries the time it was written: an avatar is replaced under the same name, and browsers
     * would otherwise keep showing the old one.
     */
    public function avatarUrl(int $userId): string
    {
        $path = $this->avatarPath($userId);

        if ($userId <= 0 || ! $this->storage->exists($path)) {
            return '';
        }

        return $this->storage->url($path) . '?v=' . $this->storage->lastModified($path);
    }

    /**
     * @param callable(string): void $generate Writes the avatar to the path it is given.
     * @throws StorageException
     * @throws \Throwable Whatever the handler throws.
     */
    public function storeAvatar(int $userId, callable $generate): void
    {
        $this->storage->storeGenerated($this->avatarPath($userId), $generate);
    }

    public function deleteAvatar(int $userId): void
    {
        $this->storage->delete($this->avatarPath($userId));
    }

    public function hasPhoto(int $userId): bool
    {
        return $userId > 0 && $this->storage->exists($this->photoPreviewPath($userId));
    }

    public function photoUrl(int $userId): string
    {
        return $this->storage->url($this->photoPath($userId));
    }

    public function photoPreviewUrl(int $userId): string
    {
        return $this->storage->url($this->photoPreviewPath($userId));
    }

    /**
     * @param callable(string): void $generate Writes the full-size photo to the path it is given.
     * @param callable(string): void $generatePreview Writes the preview to the path it is given.
     * @throws StorageException
     * @throws \Throwable Whatever the handlers throw.
     */
    public function storePhoto(int $userId, callable $generate, callable $generatePreview): void
    {
        $this->storage->storeGenerated($this->photoPath($userId), $generate);
        $this->storage->storeGenerated($this->photoPreviewPath($userId), $generatePreview);
    }

    /**
     * Take the photo of the profile from two files that are already on the disk.
     *
     * What "use this album picture as my photo" does: the pictures are on the same disk, so
     * this is a copy rather than a download and an upload.
     *
     * @throws StorageException
     */
    public function copyPhotoFrom(int $userId, string $sourcePath, string $sourcePreviewPath): void
    {
        $this->storage->copy($sourcePath, $this->photoPath($userId));
        $this->storage->copy($sourcePreviewPath, $this->photoPreviewPath($userId));
    }

    public function deletePhoto(int $userId): void
    {
        $this->storage->delete($this->photoPath($userId));
        $this->storage->delete($this->photoPreviewPath($userId));
    }

    private function avatarPath(int $userId): string
    {
        return 'users/avatar/' . $userId . '.png';
    }

    private function photoPath(int $userId): string
    {
        return 'users/photo/' . $userId . '.jpg';
    }

    private function photoPreviewPath(int $userId): string
    {
        return 'users/photo/' . $userId . '_small.jpg';
    }
}
