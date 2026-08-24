<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Modules\Admin\Application\Exceptions\RoleNotFoundException;
use Johncms\Modules\Admin\Application\Exceptions\SystemRoleNotDeletableException;

final readonly class DeleteRoleUseCase
{
    public function __construct(private RoleRepositoryInterface $roles)
    {
    }

    public function execute(int $roleId): void
    {
        $role = $this->roles->findById($roleId) ?? throw new RoleNotFoundException();

        if ($role->is_system) {
            throw new SystemRoleNotDeletableException();
        }

        // Everything granted through the role goes with it. The mirrored users.rights needs no
        // recomputing: a number on the old scale belongs to the built-in roles only, and those
        // are the ones that cannot be deleted.
        $this->roles->delete($role->id);
    }
}
