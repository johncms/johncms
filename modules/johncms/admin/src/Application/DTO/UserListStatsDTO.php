<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * The numbers above the list of users.
 */
final readonly class UserListStatsDTO
{
    public function __construct(
        public int $total,
        public int $online,
        public int $registeredToday,
        public int $banned,
        public int $pending,
        public int $unconfirmed,
    ) {
    }
}
