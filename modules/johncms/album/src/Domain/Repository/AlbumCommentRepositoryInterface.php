<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Album\Domain\Models\AlbumComment;

interface AlbumCommentRepositoryInterface
{
    /**
     * Delete every comment attached to the given photos.
     *
     * @param list<int> $photoIds
     */
    public function deleteByPhotoIds(array $photoIds): void;

    /**
     * The newest comments of every album, each with the name of the album of its photo as
     * album_name.
     *
     * @return Collection<int, AlbumComment>
     */
    public function getLatest(int $limit): Collection;

    public function countAll(): int;
}
