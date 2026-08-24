<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Admin\Application\Exceptions\RoleAccessDeniedException;

/**
 * Who may touch the role editor, and which roles.
 *
 * The permission alone is not enough. Somebody who may edit roles may hand out what they hold,
 * never what stands above them — otherwise an administrator would grant themselves supervisor and
 * the hierarchy would exist only on paper.
 */
final readonly class EnsureRoleManagementAccessUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
        private RoleLevels $roleLevels,
    ) {
    }

    /**
     * @param Role|null $role  The role being acted on, when the action names one.
     * @param int|null  $level The level the form is about to assign, when it carries one.
     */
    public function execute(?Role $role = null, ?int $level = null): void
    {
        if (! $this->accessChecker->allows(CorePermissions::ADMIN_ROLES_MANAGE)) {
            throw new RoleAccessDeniedException(__('Access denied'));
        }

        $own = $this->roleLevels->highest($this->currentUser->identity());

        if ($role !== null && $role->level > $own) {
            throw new RoleAccessDeniedException(__('You cannot manage a role that outranks your own'));
        }

        if ($level !== null && $level > $own) {
            throw new RoleAccessDeniedException(__('You cannot place a role above your own'));
        }
    }

    /**
     * The highest level the visitor may assign, which is the one they hold themselves.
     */
    public function maxLevel(): int
    {
        return $this->roleLevels->highest($this->currentUser->identity());
    }
}
