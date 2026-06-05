<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Repository;

interface AlbumVoteRepositoryInterface
{
    /**
     * Return the subset of $photoIds that the given user has already voted on.
     *
     * @param list<int> $photoIds
     * @return list<int>
     */
    public function filterVotedPhotoIds(int $userId, array $photoIds): array;
}
