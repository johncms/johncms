<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\DTO;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class UsersListResultDTO
{
    public function __construct(
        public LengthAwarePaginator $users,
    ) {
    }
}
