<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;

final readonly class UserListResultDTO
{
    /**
     * @param Collection<int, \Johncms\Users\User> $users
     */
    public function __construct(
        public Collection $users,
        public UserListSort $sort,
    ) {
    }
}
