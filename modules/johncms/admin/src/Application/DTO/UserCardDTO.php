<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Auth\Authorization\Role;
use Johncms\Users\IpHistory;
use Johncms\Users\User;

/**
 * The account and what its page shows beside it.
 */
final readonly class UserCardDTO
{
    /**
     * @param list<Role>                    $roles      Granted to the account and not run out.
     * @param array<int, int>               $activeBans Kind => when the ban ends.
     * @param Collection<int, IpHistory>    $recentIps  The latest first.
     */
    public function __construct(
        public User $user,
        public array $roles,
        public array $activeBans,
        public int $banCount,
        public Collection $recentIps,
        public int $ipCount,
    ) {
    }
}
