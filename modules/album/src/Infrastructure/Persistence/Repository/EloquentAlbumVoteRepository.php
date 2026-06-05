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
}
