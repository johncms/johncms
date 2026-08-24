<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Modules\Admin\Application\DTO\UserRoleRowDTO;
use Johncms\Modules\Admin\Application\DTO\UserRolesDTO;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Domain\Repository\UserListRepositoryInterface;

final readonly class GetUserRolesUseCase
{
    public function __construct(
        private UserListRepositoryInterface $users,
        private RoleRepositoryInterface $roles,
    ) {
    }

    /**
     * @param int $viewerLevel The level of the visitor: a role above it is listed but not offered.
     */
    public function execute(int $userId, int $viewerLevel, ?int $now = null): UserRolesDTO
    {
        $now ??= time();

        $user = $this->users->findById($userId) ?? throw new UserNotFoundException();
        $grants = $this->roles->grantsFor($userId);

        $rows = [];
        $defaultRole = null;

        /** @var Role $role */
        foreach ($this->roles->all() as $role) {
            // The guest role is what somebody has instead of an account, not something to grant.
            if ($role->is_guest) {
                continue;
            }

            // The default role applies to everybody signed in without a row of its own — which is
            // what keeps user_roles small on a site with a hundred thousand accounts.
            if ($role->is_default) {
                $defaultRole = $role->display_name;
                continue;
            }

            $expiresAt = $grants[$role->id] ?? null;

            $rows[] = new UserRoleRowDTO(
                id: $role->id,
                name: $role->display_name,
                slug: $role->slug,
                level: $role->level,
                granted: array_key_exists($role->id, $grants),
                expiresAt: $expiresAt === null ? null : date('Y-m-d', $expiresAt),
                expired: $expiresAt !== null && $expiresAt <= $now,
                manageable: $role->level <= $viewerLevel,
            );
        }

        return new UserRolesDTO($user->id, $user->name, $rows, $defaultRole);
    }
}
