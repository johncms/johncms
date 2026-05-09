<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\DTO;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class CommentsReviewResultDTO
{
    public function __construct(
        public LengthAwarePaginator $comments,
    ) {
    }
}
