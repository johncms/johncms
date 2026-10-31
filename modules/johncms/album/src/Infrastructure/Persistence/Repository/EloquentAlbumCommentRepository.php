<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Collection;
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

    public function getLatest(int $limit): Collection
    {
        return AlbumComment::query()
            ->leftJoin('cms_album_files', 'cms_album_comments.sub_id', '=', 'cms_album_files.id')
            ->leftJoin('cms_album_cat', 'cms_album_files.album_id', '=', 'cms_album_cat.id')
            ->select('cms_album_comments.*', 'cms_album_cat.name as album_name')
            ->orderByDesc('cms_album_comments.time')
            ->orderByDesc('cms_album_comments.id')
            ->limit($limit)
            ->get();
    }

    public function countAll(): int
    {
        return AlbumComment::query()->count();
    }
}
