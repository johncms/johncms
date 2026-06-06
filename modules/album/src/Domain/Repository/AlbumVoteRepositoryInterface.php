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

    /**
     * Whether the user has already voted for the given photo.
     */
    public function hasUserVote(int $userId, int $fileId): bool;

    /**
     * Store a user's vote for a photo.
     */
    public function addVote(int $userId, int $fileId, int $vote): void;

    /**
     * Delete every vote attached to the given photos.
     *
     * @param list<int> $photoIds
     */
    public function deleteByPhotoIds(array $photoIds): void;
}
