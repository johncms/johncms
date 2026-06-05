<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Repository;

interface AlbumPhotoRepositoryInterface
{
    /**
     * Count public photos uploaded after the given timestamp.
     */
    public function countNewPublicSince(int $time): int;

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
