<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Access;

use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;

/**
 * A ban goes downwards only: whoever gives, lifts or deletes one stands above the account it is on,
 * never level with it and never below — so nobody touches a ban of their own either. Holding the
 * permission is not enough on its own, or an administrator would end the ban the supervisor gave
 * another administrator.
 */
final readonly class BanHierarchy
{
    public function __construct(
        private RoleLevels $roleLevels,
        private CurrentUser $currentUser,
    ) {
    }

    public function outranks(int $targetId): bool
    {
        return $this->roleLevels->highestGrantedTo($targetId)
            < $this->roleLevels->highest($this->currentUser->identity());
    }
}
