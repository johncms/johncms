<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\DTO;

use Illuminate\Support\Collection;

final readonly class TopUsersResultDTO
{
    public function __construct(
        public Collection $users,
    ) {
    }
}
