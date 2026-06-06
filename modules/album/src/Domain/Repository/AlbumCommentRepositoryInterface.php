<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Repository;

interface AlbumCommentRepositoryInterface
{
    /**
     * Delete every comment attached to the given photos.
     *
     * @param list<int> $photoIds
     */
    public function deleteByPhotoIds(array $photoIds): void;
}
