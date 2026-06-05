<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\DTO;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class TopResultDTO
{
    /**
     * @param LengthAwarePaginator $photos Paginator whose items are PhotoViewDTO
     */
    public function __construct(
        public LengthAwarePaginator $photos,
    ) {
    }
}
