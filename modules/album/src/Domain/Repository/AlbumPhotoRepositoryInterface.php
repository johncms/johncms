<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Album\Domain\Enums\TopFilter;

interface AlbumPhotoRepositoryInterface
{
    /**
     * Count public photos uploaded after the given timestamp.
     */
    public function countNewPublicSince(int $time): int;

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
