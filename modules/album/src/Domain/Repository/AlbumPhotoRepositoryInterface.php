<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Album\Domain\Enums\TopFilter;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;

interface AlbumPhotoRepositoryInterface
{
    /**
     * Count public photos uploaded after the given timestamp.
     */
    public function countNewPublicSince(int $time): int;

    /**
     * Find a photo by id, eager-loading its album and user, or null when missing.
     */
    public function findById(int $photoId): ?AlbumPhoto;

    /**
     * Count the photos belonging to an album.
     */
    public function countByAlbum(int $albumId): int;

    /**
     * Count the photos of an album positioned before the given one
     * (i.e. with a greater id, since albums are ordered by id descending).
     */
    public function countPhotosAfter(int $albumId, int $photoId): int;

    /**
     * Paginate the photos of an album (newest first), eager-loading album and user.
     */
    public function paginatePhotosByAlbum(int $albumId, int $page, int $perPage): LengthAwarePaginator;

    /**
     * Get the album photo at the given offset (newest first), or null when out of range.
     */
    public function getPhotoByAlbumOffset(int $albumId, int $offset): ?AlbumPhoto;

    /**
     * Whether the user has already been counted as a viewer of the photo.
     */
    public function hasUserView(int $userId, int $photoId): bool;

    /**
     * Record a unique view of the photo by the user.
     */
    public function addView(int $userId, int $photoId, int $time): void;

    /**
     * Recalculate and store the cached views counter of the photo.
     */
    public function refreshViewsCount(int $photoId): void;

    /**
     * Whether the user has already been counted as a downloader of the photo.
     */
    public function hasUserDownload(int $userId, int $photoId): bool;

    /**
     * Record a unique download of the photo by the user.
     */
    public function addDownload(int $userId, int $photoId, int $time): void;

    /**
     * Recalculate and store the cached downloads counter of the photo.
     */
    public function refreshDownloadsCount(int $photoId): void;

    /**
     * Paginate photos for one of the "top" feeds, eager-loading album and user.
     *
     * For non owner-scoped feeds, when $restrictToPublicForUser is provided only
     * public photos or photos owned by that user are returned (pass null to
     * bypass, e.g. for moderators). Owner-scoped feeds use $currentUserId.
     */
    public function paginateTop(
        TopFilter $filter,
        ?int $restrictToPublicForUser,
        int $currentUserId,
        int $page,
        int $perPage
    ): LengthAwarePaginator;

    /**
     * Count visible photos grouped by owner for the given user ids.
     *
     * Returns a map of user id => photo count. When $restrictToVisibleForUser is
     * provided, only photos visible to that user (non-private) or owned by them
     * are counted. Pass null to bypass the visibility restriction.
     *
     * @param list<int> $userIds
     * @return array<int, int>
     */
    public function countByUsers(array $userIds, ?int $restrictToVisibleForUser): array;
}
