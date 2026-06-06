<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\Users\User;

interface AlbumRepositoryInterface
{
    /**
     * Find a user by id, or null when it does not exist.
     */
    public function findUserById(int $userId): ?User;

    /**
     * Find an album by id, or null when it does not exist.
     */
    public function findById(int $albumId): ?Album;

    /**
     * Get the albums owned by a user, ordered by sort.
     *
     * Each album carries a dynamic `photos_count` attribute with the total
     * number of its photos. When $restrictToVisibleForUser is provided, private
     * albums are hidden unless owned by that user (pass null to bypass, e.g. for
     * moderators).
     *
     * @return Collection<int, Album>
     */
    public function getUserAlbums(int $userId, ?int $restrictToVisibleForUser): Collection;

    /**
     * Count distinct owners of a given sex that have at least one album.
     *
     * When $restrictToVisibleForUser is provided, only albums that are visible
     * to that user (non-private) or owned by them are taken into account.
     * Pass null to count without visibility restriction (e.g. for moderators).
     */
    public function countOwnersBySex(string $sex, ?int $restrictToVisibleForUser): int;

    /**
     * Paginate album owners, optionally filtered by sex, ordered by user name.
     *
     * Each returned user model has a dynamic `count_albums` attribute holding the
     * number of their visible albums. Pass $sex = null to include every sex and
     * $restrictToVisibleForUser = null to bypass the visibility restriction.
     */
    public function paginateOwnersBySex(
        ?string $sex,
        ?int $restrictToVisibleForUser,
        int $page,
        int $perPage
    ): LengthAwarePaginator;

    /**
     * Whether the user already owns an album with the given name.
     */
    public function existsByNameForUser(int $userId, string $name): bool;

    /**
     * Create a new album for the user, appending it to the end of their sort order.
     */
    public function create(int $userId, string $name, string $description, ?string $password, int $access): Album;

    /**
     * Update the album's editable fields.
     */
    public function update(Album $album, string $name, string $description, ?string $password, int $access): void;

    /**
     * Delete the album row.
     */
    public function delete(Album $album): void;
}
