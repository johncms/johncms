<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Modules\Admin\Application\DTO\RoleFormDTO;
use Johncms\Modules\Admin\Application\Exceptions\RoleNotFoundException;
use Johncms\Modules\Admin\Application\Exceptions\RoleSlugTakenException;

final readonly class SaveRoleUseCase
{
    public function __construct(
        private RoleRepositoryInterface $roles,
        private PermissionRegistry $registry,
    ) {
    }

    /**
     * @return int The id of the role that was created or updated.
     */
    public function execute(RoleFormDTO $form, ?int $now = null): int
    {
        $now ??= time();

        if ($form->id === null) {
            if ($this->roles->findBySlug($form->slug) !== null) {
                throw new RoleSlugTakenException();
            }

            $role = $this->roles->create($form->slug, $form->name, $form->level, $now);
        } else {
            $role = $this->roles->findById($form->id) ?? throw new RoleNotFoundException();

            // A built-in role keeps the name and the level it was seeded with: code refers to
            // both, and only its permissions are the site's to change.
            if (! $role->is_system) {
                $this->roles->update($role->id, $form->name, $form->level, $now);
            }
        }

        // A role at supervisor level is allowed everything by SuperAdminVoter, so the editor does
        // not show it a matrix at all. A save must not read that as "tick nothing".
        if (! SystemRole::grantsEverything($this->levelOf($role, $form))) {
            $this->roles->setPermissions($role->id, $this->permissionsToStore($role->id, $form->permissions));
        }

        return $role->id;
    }

    /**
     * The level the role ends up with: the one the form carries, unless the role is built-in and
     * keeps its own.
     */
    private function levelOf(Role $role, RoleFormDTO $form): int
    {
        return $role->is_system ? $role->level : $form->level;
    }

    /**
     * The boxes that were ticked, plus what the editor had no way to show.
     *
     * Only declared keys are taken from the form, so a request made by hand cannot invent a
     * permission. The keys nothing declares — a module switched off, a pattern such as `forum.*` —
     * are carried over untouched: dropping them would quietly undo what the site granted.
     *
     * @param list<string> $submitted
     * @return list<string>
     */
    private function permissionsToStore(int $roleId, array $submitted): array
    {
        $kept = array_filter(
            $this->roles->permissionsFor([$roleId]),
            fn (string $key): bool => ! $this->registry->has($key)
        );

        $ticked = array_filter($submitted, fn (string $key): bool => $this->registry->has($key));

        return array_values(array_unique([...$kept, ...$ticked]));
    }
}
