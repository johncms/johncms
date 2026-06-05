<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AlbumRepositoryInterface
{
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
}
