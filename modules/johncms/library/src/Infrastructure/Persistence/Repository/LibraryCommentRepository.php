<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Library\Domain\Models\LibraryComment;
use Johncms\Modules\Library\Domain\Repository\LibraryCommentRepositoryInterface;

final class LibraryCommentRepository implements LibraryCommentRepositoryInterface
{
    public function getLatest(int $limit): Collection
    {
        return LibraryComment::query()
            ->leftJoin('library_texts', 'cms_library_comments.sub_id', '=', 'library_texts.id')
            ->select('cms_library_comments.*', 'library_texts.name as article_name')
            ->orderByDesc('cms_library_comments.time')
            ->orderByDesc('cms_library_comments.id')
            ->limit($limit)
            ->get();
    }

    public function countAll(): int
    {
        return LibraryComment::query()->count();
    }
}
