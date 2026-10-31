<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\News\Domain\Models\NewsComments;

interface NewsCommentRepositoryInterface
{
    /**
     * The newest comments that are not deleted, each with its author and its article.
     *
     * @return Collection<int, NewsComments>
     */
    public function getLatest(int $limit): Collection;

    public function countAll(): int;
}
