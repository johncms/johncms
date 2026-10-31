<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Library\Domain\Models\LibraryComment;

interface LibraryCommentRepositoryInterface
{
    /**
     * The newest comments of the library, each with the name of its article as article_name.
     *
     * @return Collection<int, LibraryComment>
     */
    public function getLatest(int $limit): Collection;

    public function countAll(): int;
}
