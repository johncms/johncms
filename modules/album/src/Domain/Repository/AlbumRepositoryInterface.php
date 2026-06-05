<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Repository;

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
}
