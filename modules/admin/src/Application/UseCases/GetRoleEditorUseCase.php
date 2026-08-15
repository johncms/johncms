<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Modules\Admin\Application\DTO\PermissionGroupDTO;
use Johncms\Modules\Admin\Application\DTO\PermissionItemDTO;
use Johncms\Modules\Admin\Application\DTO\RoleEditorDTO;
use Johncms\Modules\Admin\Application\Exceptions\RoleNotFoundException;

final readonly class GetRoleEditorUseCase
{
    public function __construct(
        private RoleRepositoryInterface $roles,
        private PermissionRegistry $registry,
    ) {
    }

    /**
     * @param int|null $roleId Null builds the editor of a role that does not exist yet.
     */
    public function execute(?int $roleId): RoleEditorDTO
    {
        $role = null;
        $granted = [];

        if ($roleId !== null) {
            $role = $this->roles->findById($roleId) ?? throw new RoleNotFoundException();
            $granted = $this->roles->permissionsFor([$role->id]);
        }

        $labels = $this->registry->groupLabels();
        $groups = [];

        foreach ($this->registry->grouped() as $group => $definitions) {
            $items = [];

            foreach ($definitions as $definition) {
                $items[] = new PermissionItemDTO(
                    $definition->key,
                    $definition->label,
                    in_array($definition->key, $granted, true)
                );
            }

            $groups[] = new PermissionGroupDTO($group, $labels[$group] ?? $group, $items);
        }

        $undeclared = array_values(
            array_filter($granted, fn (string $key): bool => ! $this->registry->has($key))
        );

        return new RoleEditorDTO($role, $groups, $undeclared);
    }
}
