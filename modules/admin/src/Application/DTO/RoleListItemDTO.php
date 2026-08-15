<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * A row of the role list.
 */
final readonly class RoleListItemDTO
{
    /**
     * @param int  $holders    Accounts holding the role explicitly; a default role has none and
     *                         applies to everybody signed in.
     * @param bool $manageable Whether the visitor may edit this role: one standing above their
     *                         own is shown, but not opened.
     * @param bool $fullAccess A role high enough to do everything, whatever permissions it
     *                         carries; the count of them means nothing for it.
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public int $level,
        public bool $isSystem,
        public bool $isDefault,
        public bool $isGuest,
        public int $permissions,
        public int $holders,
        public bool $manageable,
        public bool $fullAccess,
    ) {
    }
}
