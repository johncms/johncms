<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Modules\Admin\Application\DTO\RoleListItemDTO;

final readonly class GetRoleListUseCase
{
    public function __construct(private RoleRepositoryInterface $roles)
    {
    }

    /**
     * @param int $viewerLevel The level of the visitor: what stands above it is listed, but not
     *                         opened for editing.
     * @return list<RoleListItemDTO>
     */
    public function execute(int $viewerLevel, ?int $now = null): array
    {
        $permissionCounts = $this->roles->permissionCounts();
        $holderCounts = $this->roles->holderCounts($now ?? time());

        $items = [];

        /** @var Role $role */
        foreach ($this->roles->all() as $role) {
            $items[] = new RoleListItemDTO(
                id: $role->id,
                slug: $role->slug,
                name: $role->display_name,
                level: $role->level,
                isSystem: $role->is_system,
                isDefault: $role->is_default,
                isGuest: $role->is_guest,
                permissions: $permissionCounts[$role->id] ?? 0,
                holders: $holderCounts[$role->id] ?? 0,
                manageable: $role->level <= $viewerLevel,
                fullAccess: SystemRole::grantsEverything($role->level),
            );
        }

        return $items;
    }
}
