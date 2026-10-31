<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\News\Domain\Models\NewsComments;
use Johncms\Modules\News\Domain\Repository\NewsCommentRepositoryInterface;

final class EloquentNewsCommentRepository implements NewsCommentRepositoryInterface
{
    public function getLatest(int $limit): Collection
    {
        return NewsComments::query()
            ->with(['user', 'article'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function countAll(): int
    {
        return NewsComments::query()->count();
    }
}
