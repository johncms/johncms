<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Query;

final readonly class ForumFileListingQuery
{
    public function __construct(
        public ForumFileCountQuery $filter,
        public bool $upfp,
        public int $start,
        public int $limit,
    ) {
    }
}
