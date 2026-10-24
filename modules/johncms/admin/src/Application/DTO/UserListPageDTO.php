<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Auth\Authorization\Role;
use Johncms\Users\User;

/**
 * One page of the list of users, with what the rows show beside the account itself.
 */
final readonly class UserListPageDTO
{
    /**
     * @param Collection<int, User>  $users
     * @param array<int, list<Role>> $roles    User id => the roles granted to the account.
     * @param array<int, int>        $banEnds  User id => when the ban in force ends.
     */
    public function __construct(
        public Collection $users,
        public array $roles,
        public array $banEnds,
    ) {
    }
}
