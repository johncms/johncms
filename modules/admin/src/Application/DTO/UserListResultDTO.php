<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;

final readonly class UserListResultDTO
{
    /**
     * @param LengthAwarePaginator<\Johncms\Users\User> $users
     */
    public function __construct(
        public LengthAwarePaginator $users,
        public UserListSort $sort,
    ) {
    }
}
