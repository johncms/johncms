<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

use Johncms\Auth\Authorization\Role;

/**
 * What the role editor works with: the role itself, the catalogue of permissions with the ones it
 * carries marked, and the keys nothing declares any more.
 */
final readonly class RoleEditorDTO
{
    /**
     * @param Role|null              $role      Null while a role is being created.
     * @param list<PermissionGroupDTO> $groups
     * @param list<string>           $undeclared Permissions the role was granted that no installed
     *                                           module declares — a module switched off, or a
     *                                           pattern such as `forum.*`. Shown, kept on save,
     *                                           and never silently dropped.
     */
    public function __construct(
        public ?Role $role,
        public array $groups,
        public array $undeclared,
    ) {
    }
}
