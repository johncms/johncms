<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Infrastructure\Persistence\Repository;

use Johncms\Modules\Album\Domain\Models\AlbumComment;
use Johncms\Modules\Album\Domain\Repository\AlbumCommentRepositoryInterface;

final class EloquentAlbumCommentRepository implements AlbumCommentRepositoryInterface
{
    public function deleteByPhotoIds(array $photoIds): void
    {
        if ($photoIds === []) {
            return;
        }

        AlbumComment::query()->whereIn('sub_id', $photoIds)->delete();
    }
}
