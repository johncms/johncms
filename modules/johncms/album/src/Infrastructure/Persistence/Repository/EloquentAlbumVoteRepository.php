<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Infrastructure\Persistence\Repository;

use Johncms\Modules\Album\Domain\Models\AlbumVote;
use Johncms\Modules\Album\Domain\Repository\AlbumVoteRepositoryInterface;

final class EloquentAlbumVoteRepository implements AlbumVoteRepositoryInterface
{
    public function filterVotedPhotoIds(int $userId, array $photoIds): array
    {
        if ($photoIds === []) {
            return [];
        }

        return AlbumVote::query()
            ->where('user_id', $userId)
            ->whereIn('file_id', $photoIds)
            ->pluck('file_id')
            ->all();
    }

    public function hasUserVote(int $userId, int $fileId): bool
    {
        return AlbumVote::query()
            ->where('user_id', $userId)
            ->where('file_id', $fileId)
            ->exists();
    }

    public function addVote(int $userId, int $fileId, int $vote): void
    {
        AlbumVote::query()->create([
            'user_id' => $userId,
            'file_id' => $fileId,
            'vote'    => $vote,
        ]);
    }

    public function deleteByPhotoIds(array $photoIds): void
    {
        if ($photoIds === []) {
            return;
        }

        AlbumVote::query()->whereIn('file_id', $photoIds)->delete();
    }
}
